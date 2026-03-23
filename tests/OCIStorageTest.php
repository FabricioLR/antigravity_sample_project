<?php

namespace Tests;

use App\Storage\OCIStorage;
use Oracle\Oci\ObjectStorage\ObjectStorageClient;
use Oracle\Oci\Common\OciResponse;
use PHPUnit\Framework\TestCase;
use Exception;

class OCIStorageTest extends TestCase {
    private $clientMock;
    private OCIStorage $storage;
    private string $namespace = 'test-ns';
    private string $bucket = 'test-bucket';

    protected function setUp(): void {
        $this->clientMock = $this->createMock(ObjectStorageClient::class);
        $this->storage = new OCIStorage($this->clientMock, $this->namespace, $this->bucket);
    }

    public function testPut() {
        $this->clientMock->expects($this->once())
            ->method('putObject')
            ->willReturn($this->createMock(OciResponse::class));

        $this->storage->put('test.txt', 'hello');
    }

    public function testGet() {
        $responseMock = $this->createMock(OciResponse::class);
        $responseMock->method('getBody')->willReturn('content');

        $this->clientMock->expects($this->once())
            ->method('getObject')
            ->willReturn($responseMock);

        $this->assertEquals('content', $this->storage->get('test.txt'));
    }

    public function testExists() {
        $this->clientMock->expects($this->once())
            ->method('headObject')
            ->willReturn($this->createMock(OciResponse::class));

        $this->assertTrue($this->storage->exists('test.txt'));
    }

    public function testExistsFalse() {
        $this->clientMock->expects($this->once())
            ->method('headObject')
            ->willThrowException(new Exception("Not Found"));

        $this->assertFalse($this->storage->exists('test.txt'));
    }

    public function testDelete() {
        $this->clientMock->expects($this->once())
            ->method('deleteObject');

        $this->storage->delete('test.txt');
    }

    public function testList() {
        $responseMock = $this->createMock(OciResponse::class);
        $responseMock->method('getJson')->willReturn([
            'objects' => [
                [
                    'name' => 'user_1/file1.txt',
                    'size' => 100,
                    'timeCreated' => date('c')
                ]
            ]
        ]);

        $this->clientMock->expects($this->once())
            ->method('listObjects')
            ->willReturn($responseMock);

        $files = $this->storage->list('user_1');
        $this->assertCount(1, $files);
        $this->assertEquals('file1.txt', $files[0]['name']);
    }
}
