<?php

/**
 * Isolated tests for SectionEvent::addCard position handling.
 *
 * @package   OpenEMR
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Tests\Isolated\Events;

use OpenEMR\Events\Patient\Summary\Card\CardInterface;
use OpenEMR\Events\Patient\Summary\Card\SectionEvent;
use PHPUnit\Framework\TestCase;

class SectionEventAddCardTest extends TestCase
{
    public function testPositionZeroPrependsWhenCardsAlreadyExist(): void
    {
        $event = new SectionEvent('primary');
        $first = $this->createCardMock('first');
        $second = $this->createCardMock('second');
        $event->addCard($first);
        $event->addCard($second, 0);

        $ids = array_map(static fn (CardInterface $c) => $c->getIdentifier(), $event->getCards());
        $this->assertSame(['second', 'first'], $ids);
    }

    public function testNullPositionAppends(): void
    {
        $event = new SectionEvent('primary');
        $a = $this->createCardMock('a');
        $b = $this->createCardMock('b');
        $event->addCard($a);
        $event->addCard($b, null);

        $ids = array_map(static fn (CardInterface $c) => $c->getIdentifier(), $event->getCards());
        $this->assertSame(['a', 'b'], $ids);
    }

    private function createCardMock(string $identifier): CardInterface
    {
        $m = $this->createMock(CardInterface::class);
        $m->method('getIdentifier')->willReturn($identifier);

        return $m;
    }
}
