<?php

declare(strict_types=1);

namespace Tests\Controllers;

use PHPUnit\Framework\TestCase;
use WC_Order;

class DocumentsTest extends TestCase
{
    // Placeholder; real tests appended below.
}

<?php
/**
 * Added tests targeting Documents::setNotes behavior introduced/modified in the PR diff.
 *
 * Testing framework: PHPUnit.
 */

namespace Tests\Controllers;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

/**
 * We avoid calling the Documents constructor because it triggers a long init chain.
 * Instead, we instantiate without constructor and set only what setNotes() needs.
 */
final class DocumentsSetNotesTest extends TestCase
{
    /**
     * Build a Documents instance without running its constructor and inject a WC_Order mock.
     *
     * @param array<int,object> $orderNotes
     * @param string|null $customerNote
     * @return object [Documents-like instance]
     */
    private function makeDocumentsWithOrder(array $orderNotes, ?string $customerNote)
    {
        // Resolve Documents class via FQN search attempts commonly used in the plugin.
        $candidateClasses = [
            '\\Moloni\\Controllers\\Documents',
            '\\Moloni\\Controllers\\Documents\\Documents',
            '\\Moloni\\Documents\\Documents',
            '\\Documents', // fallback if no namespace
        ];

        $documentsClass = null;
        foreach ($candidateClasses as $cls) {
            if (class_exists($cls)) {
                $documentsClass = $cls;
                break;
            }
        }

        if ($documentsClass === null) {
            self::fail('Documents class not found. Adjust FQN in test to match project namespace.');
        }

        $ref = new \ReflectionClass($documentsClass);
        $instance = $ref->newInstanceWithoutConstructor();

        /** @var MockObject&\WC_Order $order */
        $order = $this->getMockBuilder(\WC_Order::class)
            ->onlyMethods(['get_customer_order_notes', 'get_customer_note', 'get_id'])
            ->getMock();

        $order->method('get_customer_order_notes')->willReturn($orderNotes);
        $order->method('get_customer_note')->willReturn($customerNote);
        $order->method('get_id')->willReturn(1234);

        // Inject order and default notes using reflection (properties may be private/protected).
        $this->setProperty($instance, 'order', $order);
        $this->setProperty($instance, 'orderId', 1234);
        $this->setProperty($instance, 'notes', '');

        return $instance;
    }

    /**
     * Helper to set arbitrary property regardless of visibility.
     */
    private function setProperty(object $obj, string $prop, $value): void
    {
        $rc = new \ReflectionObject($obj);
        while ($rc) {
            if ($rc->hasProperty($prop)) {
                $rp = $rc->getProperty($prop);
                $rp->setAccessible(true);
                $rp->setValue($obj, $value);
                return;
            }
            $rc = $rc->getParentClass();
        }
        self::fail("Property '$prop' not found on object of class " . get_class($obj));
    }

    /**
     * Extract notes value regardless of visibility.
     */
    private function getNotes(object $obj): string
    {
        $rc = new \ReflectionObject($obj);
        while ($rc) {
            if ($rc->hasProperty('notes')) {
                $rp = $rc->getProperty('notes');
                $rp->setAccessible(true);
                return (string)$rp->getValue($obj);
            }
            $rc = $rc->getParentClass();
        }
        self::fail("Property 'notes' not found on object of class " . get_class($obj));
    }

    public function test_setNotes_joins_multiple_customer_order_notes_with_br_delimiter(): void
    {
        $orderNotes = [
            (object)['comment_content' => 'First note'],
            (object)['comment_content' => 'Second note'],
            (object)['comment_content' => 'Third note'],
        ];

        $doc = $this->makeDocumentsWithOrder($orderNotes, 'Fallback should not be used');

        // Call the method under test.
        $result = $doc->setNotes();

        // Assert fluent interface returns same instance.
        $this->assertSame($doc, $result);

        // Notes should be joined with '<br>' and have no trailing delimiter.
        $this->assertSame('First note<br>Second note<br>Third note', $this->getNotes($doc));
    }

    public function test_setNotes_falls_back_to_single_customer_note_when_no_order_notes(): void
    {
        $doc = $this->makeDocumentsWithOrder([], 'Single customer note');

        $doc->setNotes();

        $this->assertSame('Single customer note', $this->getNotes($doc));
    }

    public function test_setNotes_uses_empty_string_when_no_notes_anywhere(): void
    {
        $doc = $this->makeDocumentsWithOrder([], null);

        $doc->setNotes();

        $this->assertSame('', $this->getNotes($doc));
    }

    #[RunInSeparateProcess]
    public function test_setNotes_is_noop_when_ADD_ORDER_NOTES_is_defined_as_Boolean_NO(): void
    {
        // Define Boolean enum/class constants value if not autoloaded. We simulate minimal behavior.
        // If project provides Boolean::NO/YES, this test will use it; otherwise, define a shim.
        if (!class_exists('\\Boolean')) {
            eval('class Boolean { const NO = 0; const YES = 1; }');
        }

        if (!defined('ADD_ORDER_NOTES')) {
            define('ADD_ORDER_NOTES', \Boolean::NO);
        }

        $doc = $this->makeDocumentsWithOrder(
            [(object)['comment_content' => 'Should be ignored']],
            'Should also be ignored'
        );

        // Pre-set a sentinel to ensure no-op preserves existing value.
        $this->setProperty($doc, 'notes', 'pre-existing');

        $doc->setNotes();

        $this->assertSame('pre-existing', $this->getNotes($doc), 'setNotes should be a no-op when disabled via constant');
    }

    public function test_setNotes_handles_single_order_note_without_adding_trailing_br(): void
    {
        $doc = $this->makeDocumentsWithOrder([(object)['comment_content' => 'Only one']], 'fallback');

        $doc->setNotes();

        $this->assertSame('Only one', $this->getNotes($doc));
    }

    public function test_setNotes_trims_nothing_and_preserves_html_in_notes_content(): void
    {
        $doc = $this->makeDocumentsWithOrder(
            [
                (object)['comment_content' => 'Line <b>bold</b>'],
                (object)['comment_content' => '<i>italic</i> line'],
            ],
            null
        );

        $doc->setNotes();

        $this->assertSame('Line <b>bold</b><br><i>italic</i> line', $this->getNotes($doc));
    }
}