<?php

namespace App\Repository;

use App\Entity\Property;
use App\Entity\PropertySearch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Property|null find($id, $lockMode = null, $lockVersion = null)
 * @method Property|null findOneBy(array $criteria, array $orderBy = null)
 * @method Property[]    findAll()
 * @method Property[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PropertyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Property::class);
    }

    /**
     * @param PropertySearch $search
     * @return Query
     */
    public function findAllVisibleQuery(PropertySearch $search): Query {
        $query = $this->findVisibleQuery();
        if ($search->getMaxPrice()) {
            $query = $query
                ->andWhere('p.price <= :maxprice')
                ->setParameter('maxprice', $search->getMaxPrice());
        }
        if ($search->getMinSurface()) {
            $query = $query
                ->andWhere('p.surface >= :minsurface')
                ->setParameter('minsurface', $search->getMinSurface());
        }
        if($search->getLat() && $search->getLng()) {
            $query = $query
                ->select('p')
                ->andWhere('(6353 * 2 * ASIN(SQRT( POWER(SIN((p.lat - :lat) * pi()/180 / 2), 2) + COS(p.lat * pi()/180) * COS(:lat * pi()/180) * POWER(SIN((p.lng - :lng) * pi()/180 / 2), 2) ))) <= :distance')
                ->setParameter('lng', $search->getLng())
                ->setParameter('lat', $search->getLat())
                ->setParameter('distance', $search->getDistance());
        }
        if ($search->getSelections()->count() > 0) {
            $k = 0;
            foreach($search->getSelections() as $selection){
                $k++;
                $query = $query
                    ->andWhere(":selection$k MEMBER OF p.selections")
                    ->setParameter("selection$k", $selection);
            }
        }
        return $query->getQuery();
    }

    /**
     * @return Property[]
     */
    public function findLatest(): array	{
        return $this->findVisibleQuery()
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();
    }

    private function findVisibleQuery(): QueryBuilder {
        return $this->createQueryBuilder('p')
            ->where('p.sold = false');
    }
}
