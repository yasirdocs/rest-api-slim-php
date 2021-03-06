<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Exception\User;
use App\Repository\UserRepository;
use App\Service\BaseService;
use App\Service\RedisService;
use Respect\Validation\Validator as v;

abstract class Base extends BaseService
{
    private const REDIS_KEY = 'user:%s';

    protected UserRepository $userRepository;

    protected RedisService $redisService;

    public function __construct(UserRepository $userRepository, RedisService $redisService)
    {
        $this->userRepository = $userRepository;
        $this->redisService = $redisService;
    }

    protected static function validateUserName(string $name): string
    {
        if (! v::alnum()->length(2, 100)->validate($name)) {
            throw new User('Invalid name.', 400);
        }

        return $name;
    }

    protected static function validateEmail(string $emailValue): string
    {
        $email = filter_var($emailValue, FILTER_SANITIZE_EMAIL);
        if (! v::email()->validate($email)) {
            throw new User('Invalid email', 400);
        }

        return $email;
    }

    protected function getUserFromCache(int $userId)
    {
        $redisKey = sprintf(self::REDIS_KEY, $userId);
        $key = $this->redisService->generateKey($redisKey);
        if ($this->redisService->exists($key)) {
            $data = $this->redisService->get($key);
            $user = json_decode(json_encode($data), false);
        } else {
            $user = $this->getUserFromDb($userId);
            $this->redisService->setex($key, $user);
        }

        return $user;
    }

    protected function getUserFromDb(int $userId)
    {
        return $this->userRepository->getUser($userId);
    }

    protected function saveInCache($id, $user): void
    {
        $redisKey = sprintf(self::REDIS_KEY, $id);
        $key = $this->redisService->generateKey($redisKey);
        $this->redisService->setex($key, $user);
    }

    protected function deleteFromCache($userId): void
    {
        $redisKey = sprintf(self::REDIS_KEY, $userId);
        $key = $this->redisService->generateKey($redisKey);
        $this->redisService->del($key);
    }
}
