<?php

declare(strict_types=1);

namespace Bernard\Tests\Driver\FlatFile;

use Bernard\Driver\FlatFile\Driver;

/**
 * @author Markus Bachmann <markus.bachmann@bachi.biz>
 */
class DriverTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Driver
     */
    private $driver;
    private $baseDir;

    protected function setUp(): void
    {
        $this->baseDir = sys_get_temp_dir().\DIRECTORY_SEPARATOR.'bernard-flat';

        if (!is_dir($this->baseDir)) {
            mkdir($this->baseDir, 0777, true);
        }

        $this->driver = new Driver($this->baseDir);
    }

    protected function tearDown(): void
    {
        if ((strtoupper(substr(\PHP_OS, 0, 3)) === 'WIN')) {
            system('rd /s /q '.$this->baseDir);
        } else {
            system('rm -R '.$this->baseDir);
        }
    }

    public function testCreate(): void
    {
        $this->driver->createQueue('send-newsletter');
        $this->driver->createQueue('send-newsletter');

        $this->assertDirectoryExists($this->baseDir.\DIRECTORY_SEPARATOR.'send-newsletter');
    }

    public function testRemove(): void
    {
        $this->driver->createQueue('send-newsletter');
        $this->driver->pushMessage('send-newsletter', 'test');

        $this->driver->removeQueue('send-newsletter');

        $this->assertDirectoryDoesNotExist($this->baseDir.\DIRECTORY_SEPARATOR.'send-newsletter');
    }

    public function testRemoveQueueWithPoppedMessage(): void
    {
        $this->driver->createQueue('send-newsletter');
        $this->driver->pushMessage('send-newsletter', 'test');
        $this->driver->popMessage('send-newsletter');

        $this->driver->removeQueue('send-newsletter');

        $this->assertDirectoryDoesNotExist($this->baseDir.\DIRECTORY_SEPARATOR.'send-newsletter');
    }

    public function testPushMessage(): void
    {
        $this->driver->createQueue('send-newsletter');
        $this->driver->pushMessage('send-newsletter', 'test');

        $this->assertCount(1, glob($this->baseDir.\DIRECTORY_SEPARATOR.'send-newsletter'.\DIRECTORY_SEPARATOR.'*.job'));
    }

    public function testPushMessagePermissions(): void
    {
        $this->driver = new Driver($this->baseDir, 0770);
        $this->testPushMessage();
        $this->assertEquals('0770', substr(sprintf('%o', fileperms($this->baseDir.\DIRECTORY_SEPARATOR.'send-newsletter'.\DIRECTORY_SEPARATOR.'1.job')), -4));
    }

    public function testPopMessage(): void
    {
        $this->driver->createQueue('send-newsletter');

        $this->driver->pushMessage('send-newsletter', 'job #1');
        $this->driver->pushMessage('send-newsletter', 'job #2');
        $this->driver->pushMessage('send-newsletter', 'job #3');

        foreach (range(3, 1) as $i) {
            $driverMessage = $this->driver->popMessage('send-newsletter');
            $this->assertEquals('job #'.$i, $driverMessage->message);
        }
    }

    public function testPopMessageWhichPushedAfterTheInitialCollect(): void
    {
        $this->driver->createQueue('send-newsletter');

        $pid = pcntl_fork();

        if ($pid === -1) {
            $this->fail('Failed to fork the currently running process: '.pcntl_strerror(pcntl_get_last_error()));
        } elseif ($pid === 0) {
            // Child process pushes a message after the initial collect
            sleep(5);
            $this->driver->pushMessage('send-newsletter', 'test');
            exit;
        }

        $driverMessage = $this->driver->popMessage('send-newsletter', 10);
        $this->assertSame('test', $driverMessage->message);

        pcntl_waitpid($pid, $status);
    }

    public function testAcknowledgeMessage(): void
    {
        $this->driver->createQueue('send-newsletter');

        $this->driver->pushMessage('send-newsletter', 'job #1');

        $driverMessage = $this->driver->popMessage('send-newsletter');

        $this->driver->acknowledgeMessage('send-newsletter', $driverMessage->receipt);

        $this->assertCount(0, glob($this->baseDir.\DIRECTORY_SEPARATOR.'send-newsletter'.\DIRECTORY_SEPARATOR.'*.job'));
    }

    public function testPeekQueue(): void
    {
        $this->driver->createQueue('send-newsletter');

        for ($i = 0; $i < 10; ++$i) {
            $this->driver->pushMessage('send-newsletter', 'Job #'.$i);
        }

        $this->assertCount(3, $this->driver->peekQueue('send-newsletter', 0, 3));

        $this->assertCount(10, glob($this->baseDir.\DIRECTORY_SEPARATOR.'send-newsletter'.\DIRECTORY_SEPARATOR.'*.job'));
    }

    public function testListQueues(): void
    {
        $this->driver->createQueue('send-newsletter-1');

        $this->driver->createQueue('send-newsletter-2');
        $this->driver->pushMessage('send-newsletter-2', 'job #1');

        $this->driver->createQueue('send-newsletter-3');
        $this->driver->pushMessage('send-newsletter-3', 'job #1');
        $this->driver->pushMessage('send-newsletter-3', 'job #2');

        $this->assertCount(3, $this->driver->listQueues());
    }
}
