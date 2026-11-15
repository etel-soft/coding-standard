<?php

declare(strict_types=1);

namespace Etel\CodingStandardTests\Fixtures\PHPStan;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use stdClass;

final readonly class RepositoryCalls
{
    public function __construct(
        private ObjectManager $manager,
        private ManagerRegistry $registry,
        private NotDoctrine $other
    ) {}

    public function handle(): void
    {
        $this->manager->getRepository(className: stdClass::class)->findOneBy(criteria: ['id' => 1]);
        $this->registry->getRepository(persistentObject: stdClass::class);
        // allowed: loading the row by id, and a getRepository() of something that is not Doctrine
        $this->manager->find(className: stdClass::class, id: 1);
        /* @noinspection PhpExpressionResultUnusedInspection */
        $this->other->getRepository();
    }
}
