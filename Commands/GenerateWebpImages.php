<?php

namespace FroshWebP\Commands;

use FroshWebP\Components\ImageStack\Arguments;
use FroshWebP\Factories\WebpConvertFactory;
use FroshWebP\Models\WebPMedia;
use FroshWebP\Repositories\WebPMediaRepository;
use FroshWebP\Services\WebpEncoderFactory;
use Shopware\Commands\ShopwareCommand;
use Shopware\Components\Model\ModelManager;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GenerateWebpImages
 */
class GenerateWebpImages extends ShopwareCommand
{
    /**
     * @var ModelManager|null
     */
    private $modelManager = null;
    /**
     * @var WebPMediaRepository|null
     */
    private $webpRepository = null;

    /** @var WebpEncoderFactory|null */
    private $encoderFactory = null;

    /** @var \FroshWebP\Components\WebpEncoderInterface[]|null */
    private $runnableEncoders = null;

    /** @var |null */
    private $webpQuality = null;

    /** @var \Shopware\Bundle\MediaBundle\MediaService|null */
    private $mediaService = null;

    /**
     * GenerateWebpImages constructor.
     *
     * @param ModelManager                              $manager
     * @param \Shopware\Bundle\MediaBundle\MediaService $mediaService
     * @param WebpEncoderFactory                        $webpEncoder
     */
    public function __construct(ModelManager $manager, \Shopware\Bundle\MediaBundle\MediaService $mediaService, WebpEncoderFactory $webpEncoder, array $webpConfig)
    {
        $this->modelManager = $manager;
        $this->mediaService = $mediaService;
        $this->webpRepository = $manager->getRepository(WebPMedia::class);
        $this->encoderFactory = $webpEncoder;
        $this->runnableEncoders = WebpEncoderFactory::onlyRunnable($this->encoderFactory->getEncoders());
        $this->webpQuality = $webpConfig['webPQuality'];
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('frosh:webp:generate')
            ->setDescription('Generate webp images for all orginal images')
            ->addOption('stack', 's', InputOption::VALUE_OPTIONAL, 'process amount per iteration')
            ->addOption('offset', 'o', InputOption::VALUE_OPTIONAL, 'process amount per iteration')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'forces recreation')
            ->addOption('setCollection', 'c', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'only generates medias for specified collection')
            ->addOption('ignoreCollection', 'i', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'ignores specified collection');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        if (empty($this->runnableEncoders)) {
            $output->writeln('No suitable encoders found');

            return;
        }

        parent::initialize($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mediaCount = $this->webpRepository->countMedias($input->getOption('setCollection'), $input->getOption('ignoreCollection'));
        $offset = $input->getOption('offset') ?? 0;
        $stack = $input->getOption('stack') ?? $mediaCount;
        $output->writeln('STACK: ' . $stack);
        $output->writeln('OFFSET: ' . $offset);

        $arguments = new Arguments(
            $input->getOption('force') ?? false,
            $input->getOption('setCollection') ?? [],
            $input->getOption('ignoreCollection') ?? [],
            $input->getOption('stack') ?? $mediaCount,
            $input->getOption('offset') ?? 0
        );

        $this->buildImageStack($output, $mediaCount, $arguments);
    }

    /**
     * @param OutputInterface $output
     * @param int             $mediaCount
     * @param Arguments       $arguments
     */
    protected function buildImageStack(OutputInterface $output, int $mediaCount, Arguments $arguments)
    {
        for ($i = $arguments->getOffset(); $i <= $mediaCount + $arguments->getStack(); $i += $arguments->getStack()) {
            $stackMedia = $this->webpRepository->findByOffset($arguments->getStack(), $i,
                $arguments->getCollectionsToUse(), $arguments->getCollectionsToIgnore());
            $progress = new ProgressBar($output, count($stackMedia));
            $progress->start();
            $this->buildImagesByStack($arguments->isForce(), $output, $stackMedia, $progress);
            $progress->finish();
        }
    }

    /**
     * @param bool            $force
     * @param OutputInterface $output
     * @param array           $stackMedia
     * @param ProgressBar     $progress
     */
    protected function buildImagesByStack(bool $force, OutputInterface $output, array $stackMedia, ProgressBar $progress)
    {
        foreach ($stackMedia as $item) {
            $webpPath = str_replace($item['extension'], 'webp', $item['path']);
            if ($this->mediaService->has($webpPath)
                && !$force) {
                $progress->advance();
                continue;
            }
            try {
                $im = imagecreatefromstring($this->mediaService->read($item['path']));
                $newImgContent = WebpConvertFactory::build(
                    $im,
                    $this->runnableEncoders,
                    $this->webpQuality
                );
                imagedestroy($im);
                $this->mediaService->write($webpPath, $newImgContent);
            } catch (\Exception $e) {
                $output->writeln($item['path'] . ' => ' . $e->getMessage());
            } catch (\Throwable $e) {
                $output->writeln($item['path'] . ' => ' . $e->getMessage());
            }
            $progress->advance();
        }
    }
}
