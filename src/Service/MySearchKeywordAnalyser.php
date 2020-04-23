<?php
namespace MNExtendSearch\Service;

use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\Product\SearchKeyword\AnalyzedKeywordCollection;
use Shopware\Core\Content\Product\SearchKeyword\AnalyzedKeyword;
use Shopware\Core\Content\Product\SearchKeyword\ProductSearchKeywordAnalyzerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Term\TokenizerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\Context;

class MySearchKeywordAnalyser implements ProductSearchKeywordAnalyzerInterface
{
    /**
     * @var ProductSearchKeywordAnalyzerInterface
     */
    private $coreAnalyzer;


    /**
     * @var TokenizerInterface
     */
    private $tokenizer;

    /**
     * @var EntityRepositoryInterface
     */
    private $categoryRepository;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(ProductSearchKeywordAnalyzerInterface $coreAnalyzer, TokenizerInterface $tokenizer, SystemConfigService $systemConfigService, EntityRepositoryInterface $categoryRepository)
    {
        $this->coreAnalyzer = $coreAnalyzer;
        $this->tokenizer = $tokenizer;
        $this->systemConfigService = $systemConfigService;
        $this->categoryRepository = $categoryRepository;
    }
    public function analyze(ProductEntity $product, Context $context): AnalyzedKeywordCollection
    {

        $keywords = $this->coreAnalyzer->analyze($product, $context);

        if ($this->systemConfigService->get('MNExtendSearch.config.description') == true) {
            $description = $product->getTranslation('description');

            $ranking = $this->systemConfigService->get('MNExtendSearch.config.rankingdescription');

            if ($description) {
                $tokens = $this->tokenizer->tokenize((string) $description);
                foreach ($tokens as $token) {
                    $keywords->add(new AnalyzedKeyword((string) $token, $ranking));
                }
            }
        }

        if ($this->systemConfigService->get('MNExtendSearch.config.metadescription') == true) {
            $metadescription = $product->getTranslation('metaDescription');

            $ranking = $this->systemConfigService->get('MNExtendSearch.config.rankingmetadescription');

            if ($metadescription) {
                $tokens = $this->tokenizer->tokenize((string) $metadescription);
                foreach ($tokens as $token) {
                    $keywords->add(new AnalyzedKeyword((string) $token, $ranking));
                }
            }
        }

        if ($this->systemConfigService->get('MNExtendSearch.config.categories') == true) {

            $categories = $product->getCategoryTree();
            $categories = $this->categoryRepository->search(
                new Criteria($categories),
                \Shopware\Core\Framework\Context::createDefaultContext()
            );

            $ranking = $this->systemConfigService->get('MNExtendSearch.config.rankingcategories');

            if ($categories) {
                foreach ($categories as $category) {
                    $categoryName = $category->getTranslation('name');
                    $keywords->add(new AnalyzedKeyword((string) $categoryName, $ranking));
                }
            }
        }

        return $keywords;
    }
}