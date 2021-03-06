<?php

declare(strict_types=1);

namespace App\Controller\User;

use App\Controller\BaseController;
use App\Exception\User;
use App\Service\User\UserService;
use Slim\Container;

abstract class Base extends BaseController
{
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    protected function getUserService(): UserService
    {
        return $this->container->get('user_service');
    }

    protected function checkUserPermissions(int $userId, int $userIdLogged): void
    {
        if ($userId !== $userIdLogged) {
            throw new User('User permission failed.', 400);
        }
    }
}
