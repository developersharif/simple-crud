<?php
namespace SimpleCrud\Tests;

use SimpleCrud\Database;
use function Latitude\QueryBuilder\field;

class QueriesTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createMysqlDatabase([
            'DROP DATABASE IF EXISTS `simple_crud`',
            'CREATE DATABASE `simple_crud`',
            'USE `simple_crud`',
            <<<'EOT'
CREATE TABLE `post` (
    `id`    int(11) unsigned NOT NULL AUTO_INCREMENT,
    `title` varchar(100) DEFAULT '',
    `body`  text,
    `num`   decimal(10,0) DEFAULT NULL,
    `point` point DEFAULT NULL,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOT
        ]);
    }

    public function testCreation(): Database
    {
        $db = $this->createDatabase();

        $this->assertInstanceOf(Database::class, $db);

        return $db;
    }

    public function dataProviderQueries()
    {
        return [
            ['select'],
            ['insert'],
            ['update'],
            ['delete'],
            ['count'],
            ['sum'],
            ['avg'],
            ['min'],
            ['max'],
        ];
    }

    /**
     * @dataProvider dataProviderQueries
     * @depends testCreation
     */
    public function testQueries(string $name, Database $db)
    {
        $query = $db->post->$name();

        $this->assertInstanceOf('SimpleCrud\\Engine\\Mysql\\Query\\'.ucfirst($name), $query);
        $this->assertInstanceOf('SimpleCrud\\Engine\\QueryInterface', $query);
    }

    /**
     * @depends testCreation
     */
    public function testSelect(Database $db)
    {
        $query = $db->post->select()
            ->one()
            ->where(field('title')->isNotNull())
            ->andWhere(field('id')->in(1, 2))
            ->andWhere($db->post->body->criteria()->eq('content'))
            ->offset(3)
            ->orderBy('title');

        $q = $query->compile();

        $this->assertEquals('SELECT `post`.`id`, `post`.`title`, `post`.`body`, `post`.`num`, `post`.`point` FROM `post` WHERE `title` IS NOT NULL AND `id` IN (?, ?) AND `post`.`body` = ? ORDER BY `title` LIMIT 1 OFFSET 3', $q->sql());
        $this->assertEquals([1, 2, 'content'], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testSelectPage(Database $db)
    {
        $query = $db->post->select()
            ->one()
            ->where(field('title')->isNotNull())
            ->andWhere(field('id')->in(1, 2))
            ->page(2, 5)
            ->orderBy('title');

        $q = $query->compile();

        $this->assertEquals('SELECT `post`.`id`, `post`.`title`, `post`.`body`, `post`.`num`, `post`.`point` FROM `post` WHERE `title` IS NOT NULL AND `id` IN (?, ?) ORDER BY `title` LIMIT 5 OFFSET 5', $q->sql());
        $this->assertEquals([1, 2], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testInsert(Database $db)
    {
        $query = $db->post->insert([
                'title' => 'Title',
                'body' => 'Body',
                'point' => [222, 333],
            ]);
        $q = $query->compile();

        $this->assertEquals('INSERT INTO `post` (`title`, `body`, `point`) VALUES (?, ?, POINT(?, ?))', $q->sql());
        $this->assertEquals(['Title', 'Body', 222, 333], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testUpdate(Database $db)
    {
        $query = $db->post->update([
                'title' => 'Title',
                'body' => 'Body',
                'point' => [23, 45],
            ])
            ->where(field('id')->eq(3));

        $q = $query->compile();

        $this->assertEquals('UPDATE `post` SET `title` = ?, `body` = ?, `point` = POINT(?, ?) WHERE `id` = ?', $q->sql());
        $this->assertEquals(['Title', 'Body', 23, 45, 3], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testDelete(Database $db)
    {
        $query = $db->post->delete()
            ->where(field('id')->eq(3));

        $q = $query->compile();

        $this->assertEquals('DELETE FROM `post` WHERE `id` = ?', $q->sql());
        $this->assertEquals([3], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testCount(Database $db)
    {
        $query = $db->post->count()
            ->where(field('id')->eq(3));

        $q = $query->compile();

        $this->assertEquals('SELECT COUNT(`id`) FROM `post` WHERE `id` = ?', $q->sql());
        $this->assertEquals([3], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testSum(Database $db)
    {
        $query = $db->post->sum('id')
            ->where(field('id')->lt(3));

        $q = $query->compile();

        $this->assertEquals('SELECT SUM(`id`) FROM `post` WHERE `id` < ?', $q->sql());
        $this->assertEquals([3], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testMax(Database $db)
    {
        $query = $db->post->max('id')
            ->where(field('id')->lt(3));

        $q = $query->compile();

        $this->assertEquals('SELECT MAX(`id`) FROM `post` WHERE `id` < ?', $q->sql());
        $this->assertEquals([3], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testMin(Database $db)
    {
        $query = $db->post->min('id')
            ->where(field('id')->lt(3));

        $q = $query->compile();

        $this->assertEquals('SELECT MIN(`id`) FROM `post` WHERE `id` < ?', $q->sql());
        $this->assertEquals([3], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }

    /**
     * @depends testCreation
     */
    public function testAvg(Database $db)
    {
        $query = $db->post->avg('id')
            ->where(field('id')->lt(3));

        $q = $query->compile();

        $this->assertEquals('SELECT AVG(`id`) FROM `post` WHERE `id` < ?', $q->sql());
        $this->assertEquals([3], $q->params());

        $result = $query();
        $this->assertInstanceOf('PDOStatement', $result);
    }
}
