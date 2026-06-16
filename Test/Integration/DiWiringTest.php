<?php

declare(strict_types=1);

namespace MageOS\Seo\Test\Integration;

use Magento\TestFramework\Helper\Bootstrap;
use MageOS\Seo\Api\FaqCollectorInterface;
use MageOS\Seo\Api\OrganisationRepositoryInterface;
use MageOS\Seo\Model\Faq\SourcePool as FaqSourcePool;
use MageOS\Seo\Model\Hreflang\ResolverPool as HreflangResolverPool;
use MageOS\Seo\Model\Hreflang\SitemapGenerator as HreflangSitemapGenerator;
use MageOS\Seo\Model\LlmsTxt\LlmsTxtBuilder;
use MageOS\Seo\Model\MetaTag\Compositor as MetaTagCompositor;
use MageOS\Seo\Model\PageTitle\Compositor as PageTitleCompositor;
use MageOS\Seo\Model\Product\OfferEnricher\Pool as OfferEnricherPool;
use MageOS\Seo\Model\Product\SchemaBuilderPool;
use MageOS\Seo\Model\Review\AggregateRatingResolver;
use MageOS\Seo\Model\RobotsMeta\Resolver as RobotsMetaResolver;
use MageOS\Seo\Model\StructuredData\Compositor as StructuredDataCompositor;
use PHPUnit\Framework\TestCase;

/**
 * Smoke-test that key services are correctly wired in the DI container.
 * Each test verifies that the ObjectManager can instantiate a class with
 * its full dependency tree, catching misconfigured di.xml entries early.
 *
 * @magentoAppArea frontend
 */
class DiWiringTest extends TestCase
{
    public function testOrganisationRepositoryIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(OrganisationRepositoryInterface::class);
        $this->assertInstanceOf(OrganisationRepositoryInterface::class, $instance);
    }

    public function testStructuredDataCompositorIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(StructuredDataCompositor::class);
        $this->assertInstanceOf(StructuredDataCompositor::class, $instance);
    }

    public function testMetaTagCompositorIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(MetaTagCompositor::class);
        $this->assertInstanceOf(MetaTagCompositor::class, $instance);
    }

    public function testPageTitleCompositorIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(PageTitleCompositor::class);
        $this->assertInstanceOf(PageTitleCompositor::class, $instance);
    }

    public function testSchemaBuilderPoolIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(SchemaBuilderPool::class);
        $this->assertInstanceOf(SchemaBuilderPool::class, $instance);
    }

    public function testLlmsTxtBuilderIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(LlmsTxtBuilder::class);
        $this->assertInstanceOf(LlmsTxtBuilder::class, $instance);
    }

    public function testRobotsMetaResolverIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(RobotsMetaResolver::class);
        $this->assertInstanceOf(RobotsMetaResolver::class, $instance);
    }

    public function testOfferEnricherPoolIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(OfferEnricherPool::class);
        $this->assertInstanceOf(OfferEnricherPool::class, $instance);
    }

    public function testAggregateRatingResolverIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(AggregateRatingResolver::class);
        $this->assertInstanceOf(AggregateRatingResolver::class, $instance);
    }

    public function testHreflangResolverPoolIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(HreflangResolverPool::class);
        $this->assertInstanceOf(HreflangResolverPool::class, $instance);
    }

    public function testHreflangSitemapGeneratorIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(HreflangSitemapGenerator::class);
        $this->assertInstanceOf(HreflangSitemapGenerator::class, $instance);
    }

    public function testFaqSourcePoolIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(FaqSourcePool::class);
        $this->assertInstanceOf(FaqSourcePool::class, $instance);
    }

    public function testFaqCollectorIsInstantiableViaDi(): void
    {
        $instance = Bootstrap::getObjectManager()->get(FaqCollectorInterface::class);
        $this->assertInstanceOf(FaqCollectorInterface::class, $instance);
    }
}
