<?php

namespace Tests\Unit\Models;

use App\Models\Item;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    /**
     * @return array<string, array{int, int, bool}>
     */
    public static function stockLevels(): array
    {
        return [
            'out of stock' => [0, 10, true],
            'below reorder level' => [9, 10, true],
            'at reorder level' => [10, 10, true],
            'above reorder level' => [11, 10, false],
        ];
    }

    #[Test]
    #[DataProvider('stockLevels')]
    public function it_flags_low_stock(int $quantity, int $reorderLevel, bool $expected): void
    {
        $item = new Item(['quantity' => $quantity, 'reorder_level' => $reorderLevel]);

        $this->assertSame($expected, $item->isLowStock());
    }

    #[Test]
    public function new_items_default_to_empty_stock_and_a_reorder_level_of_ten(): void
    {
        $item = new Item;

        $this->assertSame(0, $item->quantity);
        $this->assertSame(10, $item->reorder_level);
    }

    #[Test]
    public function price_is_kept_to_two_decimals(): void
    {
        $this->assertSame('4.50', (new Item(['price' => 4.5]))->price);
    }
}
