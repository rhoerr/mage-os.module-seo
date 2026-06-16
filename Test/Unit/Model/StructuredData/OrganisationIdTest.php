<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Unit\Model\StructuredData;

use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Api\Data\WebsiteInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageOS\Seo\Api\Data\OrganisationInterface;
use MageOS\Seo\Api\OrganisationRepositoryInterface;
use MageOS\Seo\Model\StructuredData\OrganisationId;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class OrganisationIdTest extends TestCase
{
    /**
     * @var OrganisationRepositoryInterface&MockObject
     */
    private OrganisationRepositoryInterface&MockObject $repository;

    /**
     * @var StoreManagerInterface&MockObject
     */
    private StoreManagerInterface&MockObject $storeManager;

    /**
     * @var OrganisationId
     */
    private OrganisationId $organisationId;

    protected function setUp(): void
    {
        $this->repository     = $this->createMock(OrganisationRepositoryInterface::class);
        $this->storeManager   = $this->createMock(StoreManagerInterface::class);
        $this->organisationId = new OrganisationId($this->repository, $this->storeManager);
    }

    public function testFromUrlAppendsOrganizationFragment(): void
    {
        $this->assertSame('https://acme.com/#organization', $this->organisationId->fromUrl('https://acme.com'));
    }

    public function testFromUrlStripsTrailingSlash(): void
    {
        $this->assertSame('https://acme.com/#organization', $this->organisationId->fromUrl('https://acme.com/'));
    }

    public function testGetIdWithExplicitScopeUsesRepository(): void
    {
        $org = $this->createMock(OrganisationInterface::class);
        $org->method('getUrl')->willReturn('https://acme.com/');
        $this->repository->expects($this->once())
            ->method('getForScope')->with(2, 3)->willReturn($org);

        $this->assertSame('https://acme.com/#organization', $this->organisationId->getId(2, 3));
    }

    public function testGetIdResolvesScopeFromStoreManagerWhenNotGiven(): void
    {
        $store   = $this->createMock(StoreInterface::class);
        $website = $this->createMock(WebsiteInterface::class);
        $store->method('getId')->willReturn(5);
        $website->method('getId')->willReturn(7);
        $this->storeManager->method('getStore')->willReturn($store);
        $this->storeManager->method('getWebsite')->willReturn($website);

        $org = $this->createMock(OrganisationInterface::class);
        $org->method('getUrl')->willReturn('https://acme.com');
        $this->repository->expects($this->once())
            ->method('getForScope')->with(5, 7)->willReturn($org);

        $this->assertSame('https://acme.com/#organization', $this->organisationId->getId());
    }
}
