<?php

use SimpleCrud\SimpleCrud;
use SimpleCrud\Table;

class RowTest extends PHPUnit_Framework_TestCase
{
    private static $db;

    public static function setUpBeforeClass()
    {
        self::$db = new SimpleCrud(new PDO('sqlite::memory:'));

        self::$db->executeTransaction(function ($db) {
            $db->execute(
<<<EOT
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `publishedAt` TEXT,
    `isActive`    INTEGER
);
EOT
            );
        });
    }

    public function testRow()
    {
        $db = self::$db;

        $data = [
            'title' => 'Second post',
            'publishedAt' => new DateTime(),
            'isActive' => true,
        ];

        //Test cache        
        $this->assertFalse(isset($db->post[1]));

        $db->post[] = ['title' => 'First post'];

        $this->assertTrue(isset($db->post[1]));

        //Test row
        $post = $db->post->create($data);

        $this->assertInstanceOf('SimpleCrud\\Row', $post);

        $this->assertNull($post->id);
        $this->assertSame($data['title'], $post->title);
        $this->assertSame($data['publishedAt'], $post->publishedAt);
        $this->assertSame($data['isActive'], $post->isActive);

        $this->assertFalse(isset($db->post[2]));

        $post->save();

        $this->assertSame(2, $post->id);
        $this->assertTrue(isset($db->post[2]));

        $saved = $db->post[2];

        $this->assertSame($saved, $post);

        $db->post->clearCache();

        $saved2 = $db->post[2];

        $this->assertNotSame($saved2, $post);

        $this->assertEquals($saved2->toArray(), $post->toArray());
    }

    public function testRowCollection()
    {
        $db = self::$db;

        $db->post[] = ['title' => 'One'];
        $db->post[] = ['title' => 'Two'];

        $posts = $db->post->select()
            ->by('title', ['One', 'Two'])
            ->run();

        $this->assertInstanceOf('SimpleCrud\\RowCollection', $posts);

        $this->assertCount(2, $posts);

        $this->assertEquals([3 => 'One', 4 => 'Two'], $posts->title);

        $this->assertInstanceOf('SimpleCrud\\Row', $posts[3]);
        $this->assertInstanceOf('SimpleCrud\\Row', $posts[4]);

        $this->assertSame($posts[3], $db->post[3]);

        $filtered = $posts->filter(function ($row) {
            return $row->title === 'One';
        });

        $this->assertCount(1, $filtered);

        $found = $posts->find(function ($row) {
            return $row->title === 'One';
        });

        $this->assertInstanceOf('SimpleCrud\\Row', $found);

        $this->assertSame($found, $filtered[3]);
    }
}
