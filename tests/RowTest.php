<?php
namespace SimpleCrud\Tests;

use DateTime;
use SimpleCrud\Row;

class RowTest extends AbstractTestCase
{
    private function createDatabase()
    {
        return $this->createSqliteDatabase([
            <<<'EOT'
CREATE TABLE "post" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `title`       TEXT,
    `isActive`    INTEGER,
    `publishedAt` TEXT
);
EOT
            ,
            <<<'EOT'
CREATE TABLE "comment" (
    `id`          INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT UNIQUE,
    `text`        TEXT,
    `post_id`     INTEGER
);
EOT
        ]);
    }

    public function testRow()
    {
        $db = $this->createDatabase();

        $data = [
            'title' => 'First post',
            'publishedAt' => new DateTime('04-06-2017'),
            'isActive' => true,
        ];

        $post = $db->post->create($data);

        $this->assertInstanceOf(Row::class, $post);

        $this->assertNull($post->id);
        $this->assertSame($data['title'], $post->title);
        $this->assertSame($data['publishedAt'], $post->publishedAt);
        $this->assertSame($data['isActive'], $post->isActive);

        $post->save();

        $this->assertSame(1, $post->id);
        $this->assertTrue(isset($post->comment));
        $this->assertTrue(isset($db->post[1]));
        $this->assertSame($post, $db->post[1]);

        $post->delete();
        $this->assertNull($post->id);
        $this->assertFalse(isset($db->post[1]));
    }

    public function testExtraData()
    {
        $db = $this->createDatabase();

        $db->post[] = [
            'title' => 'First post',
            'publishedAt' => new DateTime('04-06-2017'),
            'isActive' => true,
        ];

        $db->comment[] = [
            'text' => 'First comment',
            'post_id' => 1,
        ];

        $commentWithPostTitle = $db->comment->select()
            ->one()
            ->columns('post.title as postTitle')
            ->joinRelation($db->post)
            ->where('comment.id = 1')
            ->run();

        $this->assertInstanceOf(Row::class, $commentWithPostTitle);
        $this->assertSame('First post', $commentWithPostTitle->postTitle);
    }
}
