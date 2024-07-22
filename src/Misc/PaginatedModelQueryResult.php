<?php

namespace Cube\Misc;

use Dammynex\Pagenator\Pagenator;

readonly class PaginatedModelQueryResult
{
    public function __construct(
        public ModelCollection $result,
        public Pagenator $pager,
    ) {
    }

    public function toResponse(): array
    {
        return array(
            'items' => model2array($this->result),
            'pages' => array(
                'next' => $this->pager->getNextPage(),
                'prev' => $this->pager->getPreviousPage(),
                'total' => $this->pager->getTotalPages(),
            )
        );
    }
}
