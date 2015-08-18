<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Tests\Integration;

use Piwik\Db;
use Piwik\Sequence;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group Core
 * @group Sequence
 */
class SequenceTest extends IntegrationTestCase
{
    public function test_create_shouldAddNewSequenceWithInitalId1()
    {
        $sequence = $this->getEmptySequence();

        $id = $sequence->create();
        $this->assertSame(0, $id);

        // verify
        $id = $sequence->getCurrentId();
        $this->assertSame(0, $id);
    }

    public function test_create_WithCustomInitialValue()
    {
        $sequence = $this->getEmptySequence();

        $id = $sequence->create(11);
        $this->assertSame(11, $id);

        // verify
        $id = $sequence->getCurrentId();
        $this->assertSame(11, $id);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Duplicate entry
     */
    public function test_create_shouldFailIfSequenceAlreadyExists()
    {
        $sequence = $this->getExistingSequence();

        $sequence->create();
    }

    public function test_getNextId_shouldGenerateNextId()
    {
        $sequence = $this->getExistingSequence();

        $this->assertNextIdGenerated($sequence, 1);
        $this->assertNextIdGenerated($sequence, 2);
        $this->assertNextIdGenerated($sequence, 3);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Sequence 'notCreatedSequence' not found
     */
    public function test_getNextId_shouldFailIfThereIsNoSequenceHavingThisName()
    {
        $sequence = $this->getEmptySequence();
        $sequence->getNextId();
    }

    public function test_getCurrentId_shouldReturnTheCurrentIdAsInt()
    {
        $sequence = $this->getExistingSequence();

        $id = $sequence->getCurrentId();
        $this->assertSame(0, $id);
    }

    public function test_getCurrentId_shouldReturnNullIfSequenceDoesNotExist()
    {
        $sequence = $this->getEmptySequence();
        $id = $sequence->getCurrentId();
        $this->assertNull($id);
    }

    public function test_exists_shouldReturnTrueIfSequenceExist()
    {
        $sequence = $this->getExistingSequence();
        $this->assertTrue($sequence->exists());
    }

    public function test_exists_shouldReturnFalseIfSequenceExist()
    {
        $sequence = $this->getEmptySequence();
        $this->assertFalse($sequence->exists());
    }

    private function assertNextIdGenerated(Sequence $sequence, $expectedId)
    {
        $id = $sequence->getNextId();
        $this->assertSame($expectedId, $id);

        // verify
        $id = $sequence->getCurrentId();
        $this->assertSame($expectedId, $id);
    }

    private function getEmptySequence()
    {
        return new Sequence('notCreatedSequence');
    }

    private function getExistingSequence()
    {
        $sequence = new Sequence('mySequence0815');
        $sequence->create();

        return $sequence;
    }
}
