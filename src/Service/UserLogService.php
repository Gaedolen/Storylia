<?php

namespace App\Service;

use App\Document\Log;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;

class UserLogService
{
    public function __construct(
        private DocumentManager $dm,
        private Security $security,
        private RequestStack $requestStack
    ) {}

    public function log(string $action, string $details = ''): void
    {
        $user = $this->security->getUser();

        $log = new Log();
        $log->setUserId($user ? $user->getUserIdentifier() : 'anonymous');
        $log->setAction($action);
        $log->setDetails($details);

        $this->dm->persist($log);
        $this->dm->flush();
    }
}
