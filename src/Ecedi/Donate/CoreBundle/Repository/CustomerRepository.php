<?php

namespace Ecedi\Donate\CoreBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

/**
 * CustomerRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerRepository extends EntityRepository
{
    // Ajout des fonctions nous permettant d'appliquer les filtres à notre query
    use FilterTrait;

    /**
    * Fonction qui retourne les Customers filtrées
    *
    * @param array $parameters
    * @param integer $limit
    *
    * @return Query
    */
    public function getCustomersListBy($parameters, $limit = false)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->add('orderBy', 'c.createdAt DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }
        if (!empty($parameters)) {
            $this->applyFilters($qb, $parameters);
        }

        return $qb->getQuery();
    }

    /**
    * Fonction pour le webservice retournant les customers en json ou xml
    *
    * @param array() $params
    *
    * @return ArrayCollection de Customer
    */
    public function findByRestParams($params)
    {
        $defaultLimit = 10;
        $defaultOffset = 0;

        // Application des paramètres par défaut si besoin
        $limit = isset($params['limit']) ? $params['limit'] : $defaultLimit;
        $limit = $limit >= 100 ? 100 : $limit; // la limit max est de 100 obligatoirement
        $offset = isset($params['offset']) ? $params['offset'] : $defaultOffset;

        $qb = $this->createQueryBuilder('c');
        if (!empty($params['since'])) {
            $createdAt = date('Y-m-d H:i:s', $params['since']);
            $qb->where('c.createdAt >= :createdAt')
               ->setParameter(':createdAt', $createdAt);
        }

        $qb->setMaxResults($limit)
           ->setFirstResult($offset)
           ->orderBy('c.createdAt', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
    * Fonction retournant le nombre total d'entités
    *
    * @return int -- le nombre total de customers
    */
    public function countAll()
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
    *  Fonction permettant l'application des filtres/params à notre objet query
    *
    * @param object $qb -- Doctrine\ORM\QueryBuilder
    * @param array $parameters -- filtres/paramètres
    */
    private function applyFilters(QueryBuilder $qb, $parameters)
    {
        foreach ($parameters as $field => $value) {
            switch ($field) {

                case 'email':       // Filtre sur l'email donateur
                case 'lastName':    // Filtre sur le nom donateur
                case 'addressCity': // Filtre sur la ville
                    if (!empty($value)) {
                        $this->matchFilter($qb, 'c', $field, 'LIKE', $value);
                    }
                    break;
                // Filtre sur le code postal
                case 'addressZipcode':
                    if (!empty($value)) {
                        $this->matchFilter($qb, 'c', $field, '=', $value);
                    }
                    break;
                // Token fourni lors de la soumission du formulaire
                case '_token':
                    break;
            }
        }
    }
    /**
    * Fonction qui retourne les Customers ayant coché la case optin
    *
    * @param integer $limit Nombre de résultats max
    * @return Query
    */
    public function getCustomersWithOptinQuery($limit)
    {
        $qb = $this->createQueryBuilder('c');
        $qb->where($qb->expr()->eq('c.optin', 1))
            ->andWhere($qb->expr()->eq('c.optinSynchronized', 0))
            ->add('orderBy', 'c.id ASC')
            ->setMaxResults($limit);

        return $qb->getQuery();
    }
    /**
    * Fonction qui reset les optinSynchronized des Customers à false
    *
    * @return Query
    */
    public function resetCustomersoptinSynchronized()
    {
        $qb = $this->createQueryBuilder('c');
        $q = $qb->update()
                ->set('c.optinSynchronized', 0)
                ->where($qb->expr()->eq('c.optinSynchronized', 1))
                ->getQuery();

        return $q->execute();
    }
}
