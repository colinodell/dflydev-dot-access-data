<?php

/*
 * This file is a part of dflydev/dot-access-data.
 * 
 * (c) Dragonfly Development Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Dflydev\DotAccessData;

class DataTest extends \PHPUnit_Framework_TestCase
{
    protected function getSampleData()
    {
        return array(
            'a' => 'A',
            'b' => array(
                'b' => 'B',
                'c' => array('C1', 'C2', 'C3'),
                'd' => array(
                    'd1' => 'D1',
                    'd2' => 'D2',
                    'd3' => 'D3',
                ),
            ),
            'c' => array('c1', 'c2', 'c3'),
            'f' => array(
                'g' => array(
                    'h' => 'FGH',
                ),
            ),
            'h' => array(
                'i' => 'I',
            ),
            'i' => array(
                'j' => 'J',
            ),
        );
    }

    protected function runSampleDataTests(DataInterface $data)
    {
        $this->assertEquals('A', $data->get('a'));
        $this->assertEquals('B', $data->get('b.b'));
        $this->assertEquals(array('C1', 'C2', 'C3'), $data->get('b.c'));
        $this->assertEquals('D3', $data->get('b.d.d3'));
        $this->assertEquals(array('c1', 'c2', 'c3'), $data->get('c'));
        $this->assertNull($data->get('foo'), 'Foo should not exist');
        $this->assertNull($data->get('f.g.h.i'));
    }

    public function testAppend()
    {
        $data = new Data($this->getSampleData());

        $data->append('a', 'B');
        $data->append('c', 'c4');
        $data->append('b.c', 'C4');
        $data->append('b.d.d3', 'D3b');
        $data->append('b.d.d4', 'D');
        $data->append('e', 'E');
        $data->append('f.a', 'b');
        $data->append('h.i', 'I2');
        $data->append('i.k.l', 'L');

        $this->assertEquals(array('A', 'B'), $data->get('a'));
        $this->assertEquals(array('c1', 'c2', 'c3', 'c4'), $data->get('c'));
        $this->assertEquals(array('C1', 'C2', 'C3', 'C4'), $data->get('b.c'));
        $this->assertEquals(array('D3', 'D3b'), $data->get('b.d.d3'));
        $this->assertEquals(array('D'), $data->get('b.d.d4'));
        $this->assertEquals(array('E'), $data->get('e'));
        $this->assertEquals(array('b'), $data->get('f.a'));
        $this->assertEquals(array('I', 'I2'), $data->get('h.i'));
        $this->assertEquals(array('L'), $data->get('i.k.l'));

        $this->setExpectedException('RuntimeException');

        $data->append('', 'broken');
    }

    public function testSet()
    {
        $data = new Data;

        $this->assertNull($data->get('a'));
        $this->assertNull($data->get('b.c'));
        $this->assertNull($data->get('d.e'));

        $data->set('a', 'A');
        $data->set('b.c', 'C');
        $data->set('d.e', array('f' => 'F', 'g' => 'G',));

        $this->assertEquals('A', $data->get('a'));
        $this->assertEquals(array('c' => 'C'), $data->get('b'));
        $this->assertEquals('C', $data->get('b.c'));
        $this->assertEquals('F', $data->get('d.e.f'));
        $this->assertEquals(array('e' => array('f' => 'F', 'g' => 'G',)), $data->get('d'));

        $this->setExpectedException('RuntimeException');

        $data->set('', 'broken');
    }

    public function testSetClobberStringInPath()
    {
        $data = new Data;

        $data->set('a.b.c', 'Should not be able to write to a.b.c.d.e');

        $this->setExpectedException('RuntimeException');

        $data->set('a.b.c.d.e', 'broken');
    }

    public function testRemove()
    {
        $data = new Data($this->getSampleData());

        $data->remove('a');
        $data->remove('b.c');
        $data->remove('b.d.d3');
        $data->remove('d');
        $data->remove('d.e.f');
        $data->remove('empty.path');

        $this->assertNull($data->get('a'));
        $this->assertNull($data->get('b.c'));
        $this->assertNull($data->get('b.d.d3'));
        $this->assertNull(null);
        $this->assertEquals('D2', $data->get('b.d.d2'));

        $this->setExpectedException('RuntimeException');

        $data->remove('', 'broken');
    }

    public function testGet()
    {
        $data = new Data($this->getSampleData());

        $this->runSampleDataTests($data);
    }

    public function testGetData()
    {
        $wrappedData = new Data(array(
            'wrapped' => array(
                'sampleData' => $this->getSampleData()
            ),
        ));

        $data = $wrappedData->getData('wrapped.sampleData');

        $this->runSampleDataTests($data);

        $this->setExpectedException('RuntimeException');

        $data = $wrappedData->getData('wrapped.sampleData.a');
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
}