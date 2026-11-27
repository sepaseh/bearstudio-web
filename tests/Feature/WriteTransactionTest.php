<?php

namespace Tests\Feature;

use App\Support\WriteTransaction;
use PDOException;
use Tests\Support\MariaDatabase;
use Tests\TestCase;

final class WriteTransactionTest extends TestCase
{
    public function test_other_connections_wait_for_the_shared_write_lock(): void
    {
        $other = MariaDatabase::connection();
        $other->exec('USE `'.$this->testDatabase.'`');
        $other->exec('SET SESSION innodb_lock_wait_timeout = 1');

        WriteTransaction::run(function () use ($other): void {
            $other->beginTransaction();
            try {
                $other->query('SELECT id FROM write_mutex WHERE id = 1 FOR UPDATE');
                self::fail('A competing writer acquired the mutex before commit.');
            } catch (PDOException $error) {
                self::assertSame(1205, $error->errorInfo[1]);
            } finally {
                $other->rollBack();
            }
        });

        $other->beginTransaction();
        try {
            self::assertSame(1, (int) $other->query('SELECT id FROM write_mutex WHERE id = 1 FOR UPDATE')->fetchColumn());
        } finally {
            $other->rollBack();
        }
    }
}
