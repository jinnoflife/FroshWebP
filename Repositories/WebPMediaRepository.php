<?php

namespace FroshWebP\Repositories;

use Shopware\Models\Media\Media;
use Shopware\Models\Media\Repository;

/**
 * Class WebPMediaRepository
 */
class WebPMediaRepository extends Repository
{
    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    public function findImages()
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('media')
            ->from(Media::class, 'media')
            ->where('media.type = :type')
            ->setParameter(':type', Media::TYPE_IMAGE)
            ->andWhere('media.albumId != -13'); // trashbinID
    }

    /**
     * @return int
     */
    public function countMedias(array $useCollecetions = [], array $ignoreCollections = [])
    {
        $medias = $this->findImages();
        if (!empty($useCollecetions)) {
            $and_cond = $medias->expr()->orX();
            foreach ($useCollecetions as $collection) {
                $and_cond->add($medias->expr()->eq('media.albumId', $medias->expr()->literal($collection)));
            }
            $medias->andWhere($and_cond);
        }
        if (!empty($ignoreCollections)) {
            $and_cond = $medias->expr()->orX();
            foreach ($ignoreCollections as $collection) {
                $and_cond->add($medias->expr()->neq('media.albumId', $medias->expr()->literal($collection)));
            }
            $medias->andWhere($and_cond);
        }

        return count($medias->getQuery()->getArrayResult());
    }

    /**
     * @param array $useCollecetions
     * @param array $ignoreCollections
     *
     * @return int
     */
    public function countByCollection(array $useCollecetions, array $ignoreCollections = [])
    {
        $medias = $this->findImages()
            ->where('media.albumId = :albumId')

            ->getQuery()->getArrayResult();

        return count($medias);
    }

    /**
     * @param int $stack
     * @param int $offset
     *
     * @return mixed
     */
    public function findByOffset(int $stack, int $offset, array $useCollecetions = [], array $ignoreCollections = [])
    {
        $medias = $this->findImages();
        if (!empty($useCollecetions)) {
            $and_cond = $medias->expr()->orX();
            foreach ($useCollecetions as $collection) {
                $and_cond->add($medias->expr()->eq('media.albumId', $medias->expr()->literal($collection)));
            }
            $medias->andWhere($and_cond);
        }
        if (!empty($ignoreCollections)) {
            $and_cond = $medias->expr()->orX();
            foreach ($ignoreCollections as $collection) {
                $and_cond->add($medias->expr()->neq('media.albumId', $medias->expr()->literal($collection)));
            }
            $medias->andWhere($and_cond);
        }

        return $medias
            ->setFirstResult($offset)
            ->setMaxResults($stack)
            ->getQuery()->getArrayResult();
    }
}
