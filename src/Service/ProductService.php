<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;
use PDO;

class ProductService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId, $category = null, $active = null){

        $query = "
            SELECT p.id, p.company_id, p.title, p.active, p.created_at
            FROM product p
            INNER JOIN product_category pc ON pc.product_id = p.id
        ";

        if ($category !== null){
            $query .= " INNER JOIN category c ON c.id = pc.cat_id";
        }
            $query .= " WHERE p.company_id = :adminUserId";

        if ($category !== null) {
            $query .= " AND c.title = :category ";
        }

        if ($active !== null) {
            $query .= " AND p.active = :active";
        }
            $query .= " ORDER BY p.created_at ASC";

        $stm = $this->pdo->prepare($query);

        $stm->bindParam(':adminUserId', $adminUserId, PDO::PARAM_INT);

        if ($category !== null) {
            $stm->bindParam(':category', $category, PDO::PARAM_STR);
        }

        if ($active !== null) {
            $stm->bindParam(':active', $active, PDO::PARAM_INT);
        }
        $stm->execute();

        return $stm;
    }

    public function getOne($id)
    {
        $stm = $this->pdo->prepare("
            SELECT *
            FROM product
            WHERE id = {$id}
        ");
        $stm->execute();

        return $stm;
    }

    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO product (
                company_id,
                title,
                price,
                active
            ) VALUES (
                {$body['company_id']},
                '{$body['title']}',
                {$body['price']},
                {$body['active']}
            )
        ");
        if (!$stm->execute())
            return false;

        $productId = $this->pdo->lastInsertId();

        $stm = $this->pdo->prepare("
            INSERT INTO product_category (
                product_id,
                cat_id
            ) VALUES (
                {$productId},
                {$body['category_id']}
            );
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$productId},
                {$adminUserId},
                'create'
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            UPDATE product
            SET company_id = {$body['company_id']},
                title = '{$body['title']}',
                price = {$body['price']},
                active = {$body['active']}
            WHERE id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = {$body['category_id']}
            WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'update'
            )
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE FROM product_category WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;
        
        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'delete'
            )
        ");

        return $stm->execute();
    }

    public function getLog($id)
    {
        $stm = $this->pdo->prepare("
            SELECT product_log.*, au.name
            FROM product_log
            INNER JOIN admin_user au ON product_log.admin_user_id = au.id
            WHERE product_id = {$id}

        ");
        $stm->execute();

        return $stm;
    }
}
