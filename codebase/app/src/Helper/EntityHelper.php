<?php

namespace App\Helper;

use App\Exceptions\ExceptionWithStatusCode;
use App\Exceptions\FaultyEntityException;
use App\JsonRequestModels\JsonRequestModel;
use DateTime;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectRepository;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validation;

class EntityHelper
{
    public const NONE = 0;
    public const REQUIRE_EXISTS = 1;
    public const REQUIRE_EXISTS_NOT = 2;

    /**
     * @throws \ReflectionException
     */
    public static function setEntityDataFromRequestModel($entity, JsonRequestModel $requestModel, array $blacklist = [])
    {
        return self::setEntityData($entity, $requestModel->toArray(), $blacklist);
    }

    public static function getEntityChanges($entity, EntityManagerInterface $entityManager): array
    {
        $unitOfWork = $entityManager->getUnitOfWork();
        $unitOfWork->computeChangeSet($entityManager->getClassMetadata(get_class($entity)), $entity);
        return array_filter($unitOfWork->getEntityChangeSet($entity), static fn($x) => $x[0] !== $x[1]);
    }

    /**
     * @param $entity
     * @param mixed $payload
     * @param array $blacklist
     * @return mixed
     * @throws \ReflectionException
     * @throws \Exception
     */
    public static function setEntityData($entity, $payload, array $blacklist = [])
    {
        $blacklist = array_flip($blacklist);
        if ($payload instanceof Json || is_array($payload)) {
            foreach ($payload as $key => $value) {
                if (isset($blacklist[$key])) {
                    continue;
                }
                $method = 'set'.ucfirst($key);
                if(method_exists($entity, $method)) {
                    $reflectedMethod = new ReflectionMethod($entity, $method);
                    $type = (string) $reflectedMethod->getParameters()[0]->getType();
                    if (is_array($value) && class_exists($type)) {
                        $embeddedEntityData = $value;
                        $value = new $type;
                        self::setEntityData($value, $embeddedEntityData);
                    }
                    if ($type === 'DateTimeInterface') {
                        if ($value === "" || $value === null) {
                            $value = null;
                        } else {
                            try {
                                $value = new DateTime($value);
                            } catch (\Exception $e) {
                                throw new ExceptionWithStatusCode("'{$value}' could not be parsed as a date", 400);
                            }
                        }
                    }
                    $entity->$method($value);
                }
            }
        }
        return $entity;
    }

    /**
     * @param $entity
     * @throws FaultyEntityException
     */
    public static function checkEntityIntegrity($entity): void
    {
        /*
         * This validator is totally fine and working, no need to use the service for it.
         * Asserts have to be done manually though through annotations.
         */
        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $errors = $validator->validate($entity);
        $output_array = [];
        preg_match_all('/(?<=\.).*(?=\:)|(?<=\n).*(?=\s\()/', (string) $errors, $output_array);
        $errors = [];
        for ($i = 0, $iMax = count($output_array[0]); $i < $iMax; $i+=2) {
            $errors[] = [
                'field' => $output_array[0][$i] ?? null,
                'message' => trim($output_array[0][$i+1] ?? null)
            ];
        }
        if (count($errors) > 0) {
            throw new FaultyEntityException($errors);
        }
    }

    /**
     * @param Collection $collection
     * @param array $criteriaArray
     * @param int $validationRule
     * @return mixed
     * @throws ExceptionWithStatusCode
     */
    public static function getSingleMatchingEntity(
        Collection $collection,
        array $criteriaArray,
        int $validationRule = EntityHelper::NONE
    ) {
        $entity = $collection->matching(self::buildEqualCriteria($criteriaArray))[0];
        switch ($validationRule) {
            case self::REQUIRE_EXISTS:
                self::requireExistingResource($entity, $criteriaArray);
                break;
            case self::REQUIRE_EXISTS_NOT:
                self::requireNotExistingResource($entity, $criteriaArray);
                break;
        }
        return $entity;
    }

    /**
     * @param Collection $collection
     * @param array $criteriaArray
     * @param int $validationRule
     * @return Collection
     * @throws ExceptionWithStatusCode
     */
    public static function getMatchingCollection(
        Collection $collection,
        array $criteriaArray,
        int $validationRule = EntityHelper::NONE
    ): Collection {
        $matchingCollection = $collection->matching(self::buildEqualCriteria($criteriaArray));
        switch ($validationRule) {
            case self::REQUIRE_EXISTS:
                self::requireExistingResource($matchingCollection, $criteriaArray);
                break;
            case self::REQUIRE_EXISTS_NOT:
                self::requireNotExistingResource($matchingCollection, $criteriaArray);
                break;
        }
        return $matchingCollection;
    }

    /**
     * @param $data
     * @param array $findBy
     * @throws ExceptionWithStatusCode
     */
    public static function requireNotExistingResource($data, array $findBy): void
    {
        $resourceExists = true;
        if (is_array($data)) {
            if (count($data) === 0) {
                $resourceExists = false;
            }
        } elseif ($data === null) {
            $resourceExists = false;
        }

        if ($resourceExists) {
            throw new ExceptionWithStatusCode(
                'Resource with '.array_key_first($findBy).' '.reset($findBy).' already exists',
                Response::HTTP_CONFLICT
            );
        }
    }

    /**
     * @param $data
     * @param array $findBy
     * @return bool
     */
    public static function checkIfResourceExists($data, array $findBy): bool
    {
        $resourceExists = true;
        if (is_array($data)) {
            if (count($data) === 0) {
                $resourceExists = false;
            }
        } elseif ($data === null) {
            $resourceExists = false;
        }
        return $resourceExists;
    }

    /**
     * @param $data
     * @param array $findBy
     * @throws ExceptionWithStatusCode
     */
    public static function requireExistingResource($data, array $findBy): void
    {
        $resourceExists = true;
        if (is_array($data)) {
            if (count($data) === 0) {
                $resourceExists = false;
            }
        } elseif ($data === null) {
            $resourceExists = false;
        }

        if (!$resourceExists) {
            throw new ExceptionWithStatusCode(
                'Resource with '.array_key_first($findBy).' '.reset($findBy).' not found',
                Response::HTTP_NOT_FOUND
            );
        }
    }

    private static function buildCriteria(
        Criteria $criteria,
        string $func,
        array $criteriaArray,
        array $whitelist = []
    ): Criteria {
        if ($whitelist) {
            $criteriaArray = array_intersect_key( $criteriaArray, array_flip( $whitelist ) );
        }
        foreach ($criteriaArray as $key => $value) {
            $criteria = $criteria->andWhere(Criteria::expr()->$func($key, $value));
        }
        return $criteria;
    }

    /**
     * @param array $criteriaArray
     * @param array $whitelist
     * @param Criteria|null $criteria
     * @return Criteria
     */
    public static function buildEqualCriteria(
        array $criteriaArray,
        array $whitelist = [],
        ?Criteria $criteria = null
    ): Criteria {
        return self::buildCriteria($criteria ?? new Criteria(), 'eq', $criteriaArray, $whitelist);
    }

    /**
     * @param array $criteriaArray
     * @param array $whitelist
     * @param Criteria|null $criteria
     * @return Criteria
     */
    public static function buildNotEqualCriteria(
        array $criteriaArray,
        array $whitelist = [],
        ?Criteria $criteria = null
    ): Criteria {
        return self::buildCriteria($criteria ?? new Criteria(), 'neq', $criteriaArray, $whitelist);
    }

    /**
     * @param array $criteriaArray
     * @param array $whitelist
     * @param Criteria|null $criteria
     * @return Criteria
     * @throws ExceptionWithStatusCode
     */
    public static function buildRangeCriteria(array $criteriaArray, array $whitelist = [], ?Criteria $criteria = null): Criteria
    {
        $criteria = $criteria ?? new Criteria();
        foreach ($criteriaArray as $key => $range) {
            if (!is_array($range) || count($range) !== 2) {
                throw new ExceptionWithStatusCode('The provided range is invalid', Response::HTTP_BAD_REQUEST);
            }
            self::buildCriteria($criteria, 'gte', [$key, $range[0]], $whitelist);
            self::buildCriteria($criteria, 'lte', [$key, $range[1]], $whitelist);
        }
        return $criteria;
    }

    /**
     * @param array $criteriaArray
     * @param array $whitelist
     * @param Criteria|null $criteria
     * @return Criteria
     */
    public static function buildContainsCriteria(
        array $criteriaArray,
        array $whitelist = [],
        ?Criteria $criteria = null
    ): Criteria {
        return self::buildCriteria($criteria ?? new Criteria(), 'contains', $criteriaArray, $whitelist);
    }

    /**
     * @param array $roles
     * @param Criteria|null $criteria
     * @return Criteria
     */
    public static function buildRolesCriteria(array $roles, ?Criteria $criteria = null): Criteria
    {
        $criteria = $criteria ?? new Criteria();
        foreach ($roles as $role) {
            self::buildCriteria($criteria, 'contains', ['roles' => '"'.$role.'"']);
        }
        return $criteria;
    }

    /**
     * @param \Doctrine\Persistence\ObjectRepository $repository
     * @param array $identifiers
     * @throws \App\Exceptions\ExceptionWithStatusCode
     */
    public static function requireExistsByRepo(ObjectRepository $repository, array $identifiers): void
    {
        $entity = $repository->findOneBy($identifiers);
        self::requireExistingResource($entity, $identifiers);
    }

    /**
     * @param \Doctrine\Persistence\ObjectRepository $repository
     * @param array $identifiers
     * @throws \App\Exceptions\ExceptionWithStatusCode
     */
    public static function requireExistsNotByRepo(ObjectRepository $repository, array $identifiers): void
    {
        $entity = $repository->findOneBy($identifiers);
        self::requireNotExistingResource($entity, $identifiers);
    }
}