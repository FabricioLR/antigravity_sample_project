<?php

namespace Tests;

use App\FileManager;
use Exception;
use PHPUnit\Framework\TestCase;

class FileManagerTest extends TestCase {
    private string $testStorageRoot = '/tmp/test_storage';
    private FileManager $fileManager;

    protected function setUp(): void {
        // Clean up test storage directory
        if (is_dir($this->testStorageRoot)) {
            system("rm -rf " . escapeshellarg($this->testStorageRoot));
        }
        mkdir($this->testStorageRoot, 0777, true);
        
        $this->fileManager = new FileManager(1, $this->testStorageRoot);
    }

    protected function tearDown(): void {
        if (is_dir($this->testStorageRoot)) {
            system("rm -rf " . escapeshellarg($this->testStorageRoot));
        }
    }

    public function testDirectoryCreation() {
        $this->assertTrue(is_dir($this->testStorageRoot . '/user_1'));
    }

    public function testUploadAndListFile() {
        // Create dummy file
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'dummy content');
        
        $fileData = [
            'name' => 'document.txt',
            'type' => 'text/plain',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK,
            'size' => 13
        ];

        $filename = $this->fileManager->uploadFile($fileData);
        $this->assertEquals('document.txt', $filename);

        $files = $this->fileManager->listFiles();
        $this->assertCount(1, $files);
        $this->assertEquals('document.txt', $files[0]['name']);
        $this->assertEquals(13, $files[0]['size']);
    }

    public function testUploadExistingFileThrowsException() {
        $tmpFile1 = tempnam(sys_get_temp_dir(), 'test1');
        file_put_contents($tmpFile1, 'dummy content 1');
        
        $fileData1 = [
            'name' => 'docs.pdf',
            'tmp_name' => $tmpFile1,
            'error' => UPLOAD_ERR_OK
        ];
        
        $this->fileManager->uploadFile($fileData1);

        $tmpFile2 = tempnam(sys_get_temp_dir(), 'test2');
        file_put_contents($tmpFile2, 'dummy content 2');
        
        $fileData2 = [
            'name' => 'docs.pdf',
            'tmp_name' => $tmpFile2,
            'error' => UPLOAD_ERR_OK
        ];

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("File already exists.");
        $this->fileManager->uploadFile($fileData2);
    }

    public function testDeleteFile() {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'dummy content');
        
        $this->fileManager->uploadFile([
            'name' => 'todelete.txt',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK
        ]);

        $this->assertCount(1, $this->fileManager->listFiles());
        
        $this->assertTrue($this->fileManager->deleteFile('todelete.txt'));
        $this->assertCount(0, $this->fileManager->listFiles());
    }

    public function testGetFilePath() {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'dummy content');
        
        $this->fileManager->uploadFile([
            'name' => 'test_path.txt',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK
        ]);

        $path = $this->fileManager->getFilePath('test_path.txt');
        $this->assertEquals($this->testStorageRoot . '/user_1/test_path.txt', $path);
        $this->assertTrue(file_exists($path));
    }

    public function testCanGetAndUpdateFileContent() {
        $tmpFile = tempnam(sys_get_temp_dir(), 'test');
        file_put_contents($tmpFile, 'initial text');
        
        $this->fileManager->uploadFile([
            'name' => 'editor_test.txt',
            'tmp_name' => $tmpFile,
            'error' => UPLOAD_ERR_OK
        ]);

        $content = $this->fileManager->getFileContent('editor_test.txt');
        $this->assertEquals('initial text', $content);

        $this->fileManager->updateFileContent('editor_test.txt', 'modified text');

        $contentAfter = $this->fileManager->getFileContent('editor_test.txt');
        $this->assertEquals('modified text', $contentAfter);
    }

    public function testBulkDelete() {
        $tmpFile1 = tempnam(sys_get_temp_dir(), 'test1');
        file_put_contents($tmpFile1, 'dummy content 1');
        
        $this->fileManager->uploadFile([
            'name' => 'bulk1.txt',
            'tmp_name' => $tmpFile1,
            'error' => UPLOAD_ERR_OK
        ]);

        $tmpFile2 = tempnam(sys_get_temp_dir(), 'test2');
        file_put_contents($tmpFile2, 'dummy content 2');
        
        $this->fileManager->uploadFile([
            'name' => 'bulk2.txt',
            'tmp_name' => $tmpFile2,
            'error' => UPLOAD_ERR_OK
        ]);

        $this->assertCount(2, $this->fileManager->listFiles());
        
        $result = $this->fileManager->bulkDelete(['bulk1.txt', 'bulk2.txt']);
        $this->assertEquals(2, $result['success']);
        $this->assertEquals(0, $result['failed']);
        $this->assertCount(0, $this->fileManager->listFiles());
        
        // Test with non-existent file
        $result = $this->fileManager->bulkDelete(['nonexistent.txt']);
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(1, $result['failed']);
    }
}
