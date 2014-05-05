<?php

namespace spec\Pim\Bundle\ExcelConnectorBundle\Excel\Reader;

use PhpSpec\ObjectBehavior;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\Archive;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\ContentCache;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\ContentCacheLoader;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\RowIterator;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\RowIteratorFactory;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\Workbook;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\WorksheetListReader;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\Relationships;
use Pim\Bundle\ExcelConnectorBundle\Excel\Reader\RelationshipsLoader;
use Prophecy\Argument;

class WorkbookSpec extends ObjectBehavior
{
    public function let(
        RelationshipsLoader $relationshipsLoader,
        ContentCacheLoader $contentCacheLoader,
        WorksheetListReader $worksheetListReader,
        RowIteratorFactory $rowIteratorFactory,
        Archive $archive
    ) {
        $this->beConstructedWith(
            $relationshipsLoader,
            $contentCacheLoader,
            $worksheetListReader,
            $rowIteratorFactory,
            $archive
        );
    }

    public function it_is_initializable()
    {
        $this->shouldHaveType('Pim\Bundle\ExcelConnectorBundle\Excel\Reader\Workbook');
    }

    public function it_returns_the_worksheet_list()
    {
        $this->getWorksheets()->shouldReturn(['sheet1', 'sheet2']);
    }

    public function it_creates_row_iterators(
        RelationshipsLoader $relationshipsLoader,
        ContentCacheLoader $contentCacheLoader,
        ContentCache $contentCache,
        WorksheetListReader $worksheetListReader,
        RowIteratorFactory $rowIteratorFactory,
        Archive $archive,
        RowIterator $rowIterator1,
        RowIterator $rowIterator2,
        Relationships $relationships
    )
    {

        $archive->extract(Argument::type('string'))->will(
            function ($args) {
                return sprintf('temp_%s', $args[0]);
            }
        );

        $relationshipsLoader->open('temp_' . Workbook::RELATIONSHIPS_PATH)
            ->shouldBeCalledTimes(1)
            ->willReturn($relationships);

        $relationships->getSharedStringsPath()->willReturn('shared_strings');
        $contentCacheLoader->open('temp_shared_strings')
            ->shouldBeCalledTimes(1)
            ->willReturn($contentCache);

        $worksheetListReader->getWorksheets($relationships, 'temp_' . Workbook::WORKBOOK_PATH)
            ->shouldBeCalledTimes(1)
            ->willReturn(['path1' => 'sheet1', 'path2' => 'sheet2']);

        $rowIteratorFactory->create($contentCache, 'temp_path1')->willReturn($rowIterator1);
        $rowIteratorFactory->create($contentCache, 'temp_path2')->willReturn($rowIterator2);

        $this->createRowIterator(0)->shouldReturn($rowIterator1);
        $this->createRowIterator(1)->shouldReturn($rowIterator2);
    }

    public function it_finds_a_worksheet_index_by_name()
    {
        $this->getWorksheetIndex('sheet2')->shouldReturn(1);
    }

    public function it_returns_null_if_a_worksheet_does_not_exist()
    {
        $this->getWorksheetIndex('sheet3')->shouldReturn(null);
    }
}
