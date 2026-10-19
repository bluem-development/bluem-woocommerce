<?php

namespace Unit;

use Bluem\Wordpress\Requests\BluemRequestRepository;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Tests\Support\RequestLookupDatabase;

require_once dirname(__DIR__) . '/Support/RequestLookupDatabase.php';

final class BluemRequestRepositoryTest extends TestCase
{
    private RequestLookupDatabase $database;
    private BluemRequestRepository $repository;

    protected function setUp(): void
    {
        $this->database = new RequestLookupDatabase();
        $this->repository = new BluemRequestRepository($this->database);
    }

    #[DataProvider('queries')]
    public function testFilterSortAndLimitContract(array $args, string $suffix, array $values): void
    {
        self::assertSame([], $this->repository->findBy(...$args));
        $sql = 'SELECT * FROM `custom_bluem_requests`' . $suffix;
        if ($values === []) {
            self::assertSame([], $this->database->prepared);
            self::assertSame([$sql], $this->database->queries);
        } else {
            self::assertSame([[$sql, $values]], $this->database->prepared);
            self::assertSame(['prepared-query'], $this->database->queries);
        }
    }

    public static function queries(): array
    {
        return [
            'unfiltered' => [[], '', []],
            'empty keys and string values skipped' => [[['' => 'x', 0 => 'y', 'type' => '']], '', []],
            'zero false null retained' => [[['user_id' => 0, 'order_id' => false, 'type' => null]], ' WHERE `user_id` = %s AND `order_id` = %s AND `type` = %s', [0, false, null]],
            'values passed as parameters' => [[['debtor_reference' => "O'Reilly %s"], 'timestamp', 'desc', '2'], ' WHERE `debtor_reference` = %s ORDER BY `timestamp` DESC LIMIT %d', ["O'Reilly %s", '2']],
            'sort only' => [[[], 'timestamp', 'asc'], ' ORDER BY `timestamp` ASC', []],
            'invalid direction omitted' => [[[], 'timestamp', 'invalid', 2], ' LIMIT %d', [2]],
            'empty sort omitted' => [[[], '', 'ASC', 1], ' LIMIT %d', [1]],
            'negative limit omitted' => [[[], null, 'ASC', -1], '', []],
            'nonnumeric limit omitted' => [[[], null, 'ASC', 'all'], '', []],
        ];
    }

    public function testSingleAndMultipleResultMethodsKeepTheirShapes(): void
    {
        $first = (object) ['id' => 17];
        $second = (object) ['id' => 23];
        $this->database->results = [$first, $second];
        foreach ([
            ['findById', ['17']], ['findByDebtorReference', ['ref']],
            ['findByTransactionId', ['tx']], ['findByTransactionIdAndType', ['tx', 'identity']],
            ['findByTransactionIdAndEntranceCode', ['tx', 'code']], ['findMostRecent', [42]],
        ] as [$method, $args]) {
            self::assertSame($first, $this->repository->$method(...$args));
        }
        self::assertSame([$first, $second], $this->repository->findForUser(42));
        self::assertSame([$first, $second], $this->repository->findForUserAndType(42, 'identity'));
        self::assertSame([$first, $second], $this->repository->findByField('user_id', 42));
    }

    public function testDatabaseReadFailuresKeepLegacyFallbacks(): void
    {
        $this->database->failRead = true;
        self::assertFalse($this->repository->findBy());
        self::assertFalse($this->repository->findById('17'));
        self::assertFalse($this->repository->findByDebtorReference('ref'));
        self::assertFalse($this->repository->findByTransactionIdAndEntranceCode('tx', 'code'));
        self::assertSame([], $this->repository->findForUser(42));
        self::assertSame([], $this->repository->findForUserAndType(42));
        self::assertFalse($this->repository->findMostRecent(42));
    }

    public function testGenericPreparationErrorsStillPropagate(): void
    {
        $this->database->failPrepare = true;
        $this->expectException(\RuntimeException::class);
        $this->repository->findBy(['user_id' => 42]);
    }

    public function testMostRecentPreparationErrorsStillReturnFalse(): void
    {
        $this->database->failPrepare = true;
        self::assertFalse($this->repository->findMostRecent(42));
    }

    public function testRawNullResultsAreNotNormalized(): void
    {
        $this->database->results = null;
        self::assertNull($this->repository->findBy());
        self::assertFalse($this->repository->findById('17'));
        self::assertFalse($this->repository->findMostRecent(42));
    }

    public function testMostRecentRetainsLegacyTypeAllowlist(): void
    {
        foreach (['ideal', 'unknown', ''] as $type) {
            self::assertFalse($this->repository->findMostRecent(42, $type));
        }
        self::assertSame([], $this->database->queries);
        self::assertSame(0, $this->database->showErrorsCalls);
        foreach (['mandates', 'payments', 'identity'] as $type) {
            $this->repository->findMostRecent(42, $type);
        }
        self::assertCount(3, $this->database->queries);
    }
}
