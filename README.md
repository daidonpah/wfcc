# wfcc - woflow coding challenge

install vagrant, php and composer and then  run
> vagrant plugin install vagrant-hostmanager

To initialize the project do
> composer install

and fire up the box with
>vagrant up

Once the vm is booted, ssh into it with
>vagrant ssh

Then cd into wfcc/app and run another `composer install`

Then init db:
> php bin/console doctrine:database:create && php bin/console doctrine:migrations:migrate --no-interaction


Now you are good to go and can reach the api under
https://wfcc.test/api/v1/nodes
