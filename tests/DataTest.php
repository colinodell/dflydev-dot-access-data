<?php

declare(strict_types=1);

/*
 * This file is a part of dflydev/dot-access-data.
 *
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dflydev\DotAccessData;

use Dflydev\DotAccessData\Exception\DataException;
use Dflydev\DotAccessData\Exception\InvalidPathException;
use PHPUnit\Framework\TestCase;

class DataTest extends TestCase
{
    protected function getSampleData()
    {
        return [
            'a' => 'A',
            'b' => [
                'b' => 'B',
                'c' => ['C1', 'C2', 'C3'],
                'd' => [
                    'd1' => 'D1',
                    'd2' => 'D2',
                    'd3' => 'D3',
                ],
            ],
            'c' => ['c1', 'c2', 'c3'],
            'f' => [
                'g' => [
                    'h' => 'FGH',
                ],
            ],
            'h' => [
                'i' => 'I',
            ],
            'i' => [
                'j' => 'J',
            ],
        ];
    }

    protected function runSampleDataTests(DataInterface $data)
    {
        $this->assertEquals('A', $data->get('a'));
        $this->assertEquals('B', $data->get('b.b'));
        $this->assertEquals('B', $data->get('b/b'));
        $this->assertEquals(['C1', 'C2', 'C3'], $data->get('b.c'));
        $this->assertEquals(['C1', 'C2', 'C3'], $data->get('b/c'));
        $this->assertEquals('D3', $data->get('b.d.d3'));
        $this->assertEquals('D3', $data->get('b/d/d3'));
        $this->assertEquals(['c1', 'c2', 'c3'], $data->get('c'));
        $this->assertNull($data->get('foo', null), 'Foo should not exist');
        $this->assertNull($data->get('f.g.h.i', null));
        $this->assertNull($data->get('f/g/h/i', null));
        $this->assertEquals($data->get('foo', 'default-value-1'), 'default-value-1', 'Return default value');
        $this->assertEquals($data->get('f.g.h.i', 'default-value-2'), 'default-value-2');
        $this->assertEquals($data->get('f/g/h/i', 'default-value-2'), 'default-value-2');

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Path cannot be an empty string');
        $data->get('', 'broken');
    }

    public function testAppend()
    {
        $data = new Data($this->getSampleData());

        $data->append('a', 'B');
        $data->append('c', 'c4');
        $data->append('b.c', 'C4');
        $data->append('b/d/d3', 'D3b');
        $data->append('b.d.d4', 'D');
        $data->append('e', 'E');
        $data->append('f/a', 'b');
        $data->append('h.i', 'I2');
        $data->append('i/k/l', 'L');

        $this->assertEquals(['A', 'B'], $data->get('a'));
        $this->assertEquals(['c1', 'c2', 'c3', 'c4'], $data->get('c'));
        $this->assertEquals(['C1', 'C2', 'C3', 'C4'], $data->get('b.c'));
        $this->assertEquals(['D3', 'D3b'], $data->get('b.d.d3'));
        $this->assertEquals(['D'], $data->get('b.d.d4'));
        $this->assertEquals(['E'], $data->get('e'));
        $this->assertEquals(['b'], $data->get('f.a'));
        $this->assertEquals(['I', 'I2'], $data->get('h.i'));
        $this->assertEquals(['L'], $data->get('i.k.l'));

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Path cannot be an empty string');
        $data->append('', 'broken');
    }

    public function testSet()
    {
        $data = new Data();

        $this->assertNull($data->get('a', null));
        $this->assertNull($data->get('b/c', null));
        $this->assertNull($data->get('d.e', null));

        $data->set('a', 'A');
        $data->set('b/c', 'C');
        $data->set('d.e', ['f' => 'F', 'g' => 'G']);

        $this->assertEquals('A', $data->get('a'));
        $this->assertEquals(['c' => 'C'], $data->get('b'));
        $this->assertEquals('C', $data->get('b.c'));
        $this->assertEquals('F', $data->get('d/e/f'));
        $this->assertEquals(['e' => ['f' => 'F', 'g' => 'G']], $data->get('d'));

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Path cannot be an empty string');
        $data->set('', 'broken');
    }

    public function testSetClobberStringInPath()
    {
        $data = new Data();

        $data->set('a.b.c', 'Should not be able to write to a.b.c.d.e');

        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Key path "c" within "a » b » c » d » e" cannot be indexed into (is not an array)');
        $data->set('a.b.c.d.e', 'broken');
    }

    public function testRemove()
    {
        $data = new Data($this->getSampleData());

        $data->remove('a');
        $data->remove('b.c');
        $data->remove('b/d/d3');
        $data->remove('d');
        $data->remove('d.e.f');
        $data->remove('empty.path');

        $this->assertNull($data->get('a', null));
        $this->assertNull($data->get('b/c', null));
        $this->assertNull($data->get('b.d.d3', null));
        $this->assertNull(null);
        $this->assertEquals('D2', $data->get('b.d.d2'));

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Path cannot be an empty string');
        $data->remove('', 'broken');
    }

    public function testGet()
    {
        $data = new Data($this->getSampleData());

        $this->runSampleDataTests($data);
    }

    public function testGetWhenValueDoesNotExist()
    {
        $data = new Data($this->getSampleData());

        // With a default parameter given:
        $this->assertSame('DEFAULT', $data->get('foo.bar', 'DEFAULT'));
        $this->assertSame(false, $data->get('foo.bar', false));
        $this->assertSame(null, $data->get('foo/bar', null));

        // Without a default parameter:
        $this->expectException(DataException::class);
        $this->expectExceptionMessage('No data exists at the given path: "foo » bar"');
        $data->get('foo.bar');
    }

    public function testHas()
    {
        $data = new Data($this->getSampleData());

        foreach (
            ['a', 'i', 'b.d', 'b/d', 'f.g.h', 'f/g/h', 'h.i', 'h/i', 'b.d.d1', 'b/d/d1'] as $existentKey
        ) {
            $this->assertTrue($data->has($existentKey));
        }

        foreach (
            ['p', 'b.b1', 'b/b1', 'b.c.C1', 'b/c/C1', 'h.i.I', 'h/i/I', 'b.d.d1.D1', 'b/d/d1/D1'] as $notExistentKey
        ) {
            $this->assertFalse($data->has($notExistentKey));
        }

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Path cannot be an empty string');
        $data->has('', 'broken');
    }

    public function testGetData()
    {
        $wrappedData = new Data([
            'wrapped' => [
                'sampleData' => $this->getSampleData()
            ],
        ]);

        $data = $wrappedData->getData('wrapped.sampleData');

        $this->runSampleDataTests($data);
    }

    public function testGetDataOnNonArrayValue()
    {
        $data = new Data([
            'foo' => 'bar',
        ]);

        $this->expectException(DataException::class);
        $this->expectExceptionMessage('Value at "foo" could not be represented as a DataInterface');
        $data->getData('foo');
    }

    public function testImport()
    {
        $data = new Data();
        $data->import($this->getSampleData());

        $this->runSampleDataTests($data);
    }

    public function testImportData()
    {
        $data = new Data();
        $data->importData(new Data($this->getSampleData()));

        $this->runSampleDataTests($data);
    }

    public function testExport()
    {
        $data = new Data($this->getSampleData());

        $this->assertEquals($this->getSampleData(), $data->export());
    }

    public function testOffsetExists()
    {
        $data = new Data($this->getSampleData());

        foreach (
            ['a', 'i', 'b.d', 'b/d', 'f.g.h', 'f/g/h', 'h.i', 'h/i', 'b.d.d1', 'b/d/d1'] as $existentKey
        ) {
            $this->assertTrue(isset($data[$existentKey]));
        }

        foreach (
            ['p', 'b.b1', 'b/b1', 'b.c.C1', 'b/c/C1', 'h.i.I', 'h/i/I', 'b.d.d1.D1', 'b/d/d1/D1'] as $notExistentKey
        ) {
            $this->assertFalse(isset($data[$notExistentKey]));
        }
    }

    public function testOffsetGet()
    {
        $wrappedData = new Data([
            'wrapped' => [
                'sampleData' => $this->getSampleData()
            ],
        ]);

        $data = $wrappedData->getData('wrapped.sampleData');

        $this->assertEquals('A', $data['a']);
        $this->assertEquals('B', $data['b.b']);
        $this->assertEquals('B', $data['b/b']);
        $this->assertEquals(['C1', 'C2', 'C3'], $data['b.c']);
        $this->assertEquals(['C1', 'C2', 'C3'], $data['b/c']);
        $this->assertEquals('D3', $data['b.d.d3']);
        $this->assertEquals('D3', $data['b/d/d3']);
        $this->assertEquals(['c1', 'c2', 'c3'], $data['c']);
        $this->assertNull($data['foo'], 'Foo should not exist');
        $this->assertNull($data['f.g.h.i']);
        $this->assertNull($data['f/g/h/i']);
    }

    public function testOffsetSet()
    {
        $data = new Data();

        $this->assertNull($data['a']);
        $this->assertNull($data['b.c']);
        $this->assertNull($data['d.e']);

        $data['a']   = 'A';
        $data['b/c'] = 'C';
        $data['d.e'] = ['f' => 'F', 'g' => 'G'];

        $this->assertEquals('A', $data['a']);
        $this->assertEquals(['c' => 'C'], $data['b']);
        $this->assertEquals('C', $data['b.c']);
        $this->assertEquals('F', $data['d/e/f']);
        $this->assertEquals(['e' => ['f' => 'F', 'g' => 'G']], $data['d']);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Path cannot be an empty string');
        $data->set('', 'broken');
    }

    public function testOffsetUnset()
    {
        $data = new Data($this->getSampleData());

        unset($data['a']);
        unset($data['b/c']);
        unset($data['b.d.d3']);
        unset($data['d']);
        unset($data['d.e.f']);
        unset($data['empty.path']);

        $this->assertNull($data['a']);
        $this->assertNull($data['b.c']);
        $this->assertNull($data['b/d/d3']);
        $this->assertNull(null);
        $this->assertEquals('D2', $data['b.d.d2']);

        $this->expectException(InvalidPathException::class);
        $this->expectExceptionMessage('Path cannot be an empty string');
        unset($data['']);
    }
}
