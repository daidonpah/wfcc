<?php


namespace App\Controller\api\v1;

use App\Entity\Node;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class NodesController extends AbstractController
{
    /**
     * @var \Doctrine\ORM\EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('nodes', methods: ["GET"])]
    public function indexGetAction(): JsonResponse
    {
        $nodes = $this->entityManager->getRepository(Node::class)->findAll();
        return $this->json(
            $nodes,
            JsonResponse::HTTP_OK,
            [],
            ['groups' => ['node']]
        );
    }

    #[Route('nodes/{nodeIdList}', methods: ["GET"])]
    public function getNodesByNodeIdListGetAction(string $nodeIdList): JsonResponse
    {
        $ids = explode(',', $nodeIdList);
        $nodes = [];
        foreach ($ids as $id) {
            $nodes[] = $this->entityManager->getRepository(Node::class)->find($id);
        }
        /**
         * @var Node[] $nodes
         * @var Node $node
         */
        $nodes = array_map(
            static fn ($node) => [
                'id' => $node->getId(),
                'child_node_ids' => $node->getChildNodeIds()
            ], $nodes
        );
        return new JsonResponse($nodes);
    }

    #[Route('nodes/new', methods: ["POST"])]
    public function createNewNodePostAction(): JsonResponse
    {
        $node = new Node();
        $this->entityManager->persist($node);
        $this->entityManager->flush();
        return new JsonResponse(['uuid' => $node->getId()], JsonResponse::HTTP_CREATED);
    }

    #[Route('nodes/{parentId}/add/{childId}', methods: ["POST"])]
    public function addNodeToNodePostAction(string $parentId, string $childId): JsonResponse
    {
        /** @var Node $parent */
        $parent = $this->entityManager->getRepository(Node::class)->find($parentId);
        /** @var Node $child */
        $child = $this->entityManager->getRepository(Node::class)->find($childId);
        $parent->addChildNode($child);
        $this->entityManager->persist($parent);
        $this->entityManager->flush();
        return new JsonResponse(null, JsonResponse::HTTP_NO_CONTENT);
    }

    #[Route('nodes/{nodeId}/analyze', methods: ["GET"])]
    public function analyzeNodeGetAction(string $nodeId): JsonResponse
    {
        /** @var Node $parent */
        $startNode = $this->entityManager->getRepository(Node::class)->find($nodeId);

        /** @var Node $mostSharedNode */
        $mostSharedNode = $startNode;
        $numberOfNodes = 0;

        $this->walkTree(
            $startNode,
            $mostSharedNode,
            $numberOfNodes
        );
        return new JsonResponse(['mostSharedNode' => $mostSharedNode->getId(), 'numberOfUniqueNodes' => $numberOfNodes], JsonResponse::HTTP_OK);
    }

    private function walkTree(Node $node, Node &$mostSharedNode, int &$numberOfNodes):void
    {
        if (count($mostSharedNode->getChildNodes()) <= count($node->getChildNodes())) {
            $mostSharedNode = $node;
        }
        $numberOfNodes++;
        if ($node->hasChildren()) {
            foreach ($node->getChildNodes() as $childNode) {
                $this->walkTree($childNode, $mostSharedNode, $numberOfNodes);
            }
        }
    }

}