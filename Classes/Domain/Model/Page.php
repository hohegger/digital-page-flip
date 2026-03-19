<?php

declare(strict_types=1);

namespace Kit\DigitalPageFlip\Domain\Model;

use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

final class Page extends AbstractEntity
{
    protected int $pageNumber = 0;

    protected ?FileReference $image = null;

    protected ?FileReference $imageFallback = null;

    protected int $sorting = 0;

    public function getPageNumber(): int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    public function getImage(): ?FileReference
    {
        return $this->image;
    }

    public function setImage(?FileReference $image): void
    {
        $this->image = $image;
    }

    public function getImageFallback(): ?FileReference
    {
        return $this->imageFallback;
    }

    public function setImageFallback(?FileReference $imageFallback): void
    {
        $this->imageFallback = $imageFallback;
    }

    public function getSorting(): int
    {
        return $this->sorting;
    }

    public function setSorting(int $sorting): void
    {
        $this->sorting = $sorting;
    }
}
