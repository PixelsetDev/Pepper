<?php

namespace Pepper\Processes\Algorithm;

use Starlight\Database\MySQL;
use Pepper\Processes\Users;

class TrendingAlgorithm
{
    private $db;
    private $userHelper;
    private $auth;
    private $decoded;

    public function __construct($auth, $decoded)
    {
        $this->db = new MySQL(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->userHelper = new Users();
        $this->auth = $auth;
        $this->decoded = $decoded;
    }

    public function Trending(): array
    {
        $query = "SELECT r.id, r.slug, r.name, r.author, r.visibility,
                (IFNULL(v_all.total_views, 0) / IFNULL(NULLIF((SELECT MAX(tv) FROM (SELECT SUM(views) as tv FROM recipes_views GROUP BY recipe_id) as m1), 0), 1)) * 0.10 as score_v_all,
                IFNULL(rev_all.pos_ratio, 0) * 0.15 as score_r_all,
                (IFNULL(c.col_count, 0) / IFNULL(NULLIF((SELECT MAX(cc) FROM (SELECT COUNT(DISTINCT collection_id) as cc FROM collections_recipes GROUP BY recipe_id) as m2), 0), 1)) * 0.15 as score_col,
                (IFNULL(v_week.v, 0) / IFNULL(NULLIF((SELECT MAX(v) FROM (SELECT SUM(views) as v FROM recipes_views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY recipe_id) as m3), 0), 1)) * 0.15 as score_v_week,
                IFNULL(rev_week.pr, 0) * 0.15 as score_r_week,
                (IFNULL(v_month.v, 0) / IFNULL(NULLIF((SELECT MAX(v) FROM (SELECT SUM(views) as v FROM recipes_views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) GROUP BY recipe_id) as m4), 0), 1)) * 0.15 as score_v_month,
                IFNULL(rev_month.pr, 0) * 0.15 as score_r_month
                FROM recipes r
                LEFT JOIN (SELECT recipe_id, SUM(views) as total_views FROM recipes_views GROUP BY recipe_id) v_all ON r.id = v_all.recipe_id
                LEFT JOIN (SELECT recipe_id, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) / COUNT(*) as pos_ratio FROM recipes_reviews GROUP BY recipe_id) rev_all ON r.id = rev_all.recipe_id
                LEFT JOIN (SELECT recipe_id, COUNT(DISTINCT collection_id) as col_count FROM collections_recipes GROUP BY recipe_id) c ON r.id = c.recipe_id
                LEFT JOIN (SELECT recipe_id, SUM(views) as v FROM recipes_views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY recipe_id) v_week ON r.id = v_week.recipe_id
                LEFT JOIN (SELECT recipe_id, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) / COUNT(*) as pr FROM recipes_reviews WHERE created >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY recipe_id) rev_week ON r.id = rev_week.recipe_id
                LEFT JOIN (SELECT recipe_id, SUM(views) as v FROM recipes_views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH) GROUP BY recipe_id) v_month ON r.id = v_month.recipe_id
                LEFT JOIN (SELECT recipe_id, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) / COUNT(*) as pr FROM recipes_reviews WHERE created >= DATE_SUB(NOW(), INTERVAL 1 MONTH) GROUP BY recipe_id) rev_month ON r.id = rev_month.recipe_id
                ORDER BY (score_v_all + score_r_all + score_col + score_v_week + score_r_week + score_v_month + score_r_month) DESC LIMIT 10";

        $recipes = $this->db->fetchAll($query);
        foreach ($recipes as $key => $recipe) {
            if (!$this->auth->canViewObject($this->decoded, $recipe['author'], (int)$recipe['visibility'], true)) { unset($recipes[$key]); continue; }
            $recipes[$key]['author'] = ['name' => $this->userHelper->uuidToName($recipe['author']), 'username' => $this->userHelper->uuidToUsername($recipe['author'])];
            unset($recipes[$key]['score_v_all'], $recipes[$key]['score_r_all'], $recipes[$key]['score_col'], $recipes[$key]['score_v_week'], $recipes[$key]['score_r_week'], $recipes[$key]['score_v_month'], $recipes[$key]['score_r_month']);
        }
        return ["collection" => ["id" => 1, "author" => ["uuid" => "SYSTEM", "name" => "SYSTEM", "username" => "SYSTEM"], "slug" => "top-10", "name" => "Top 10", "description" => "OurCookbook's most popular recipes.", "featured" => 1, "visibility" => 3], "recipes" => array_slice(array_values($recipes), 0, 10)];
    }

    public function TrendingWeek(): array
    { return $this->getTopRecipesByPeriod('7 DAY', 2, 'top-10-week', "Trending: This week", "OurCookbook's most viewed recipes this week."); }

    public function TrendingMonth(): array
    { return $this->getTopRecipesByPeriod('1 MONTH', 3, 'top-10-month', "Trending: This month", "OurCookbook's most viewed recipes this month."); }

    private function getTopRecipesByPeriod(string $interval, $id, $slug, $name, $desc)
    {
        $query = "SELECT r.id, r.slug, r.name, r.author, r.visibility,
                (IFNULL(v.period_views, 0) / NULLIF((SELECT MAX(views_sum) FROM (SELECT SUM(views) as views_sum FROM recipes_views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL $interval) GROUP BY recipe_id) as max_v), 0)) * 0.5 as view_score,
                IFNULL(rev.pos_ratio, 0) * 0.5 as review_score
                FROM recipes r
                LEFT JOIN (SELECT recipe_id, SUM(views) as period_views FROM recipes_views WHERE view_date >= DATE_SUB(CURDATE(), INTERVAL $interval) GROUP BY recipe_id) v ON r.id = v.recipe_id
                LEFT JOIN (SELECT recipe_id, SUM(CASE WHEN rating >= 4 THEN 1 ELSE 0 END) / COUNT(*) as pos_ratio FROM recipes_reviews WHERE created >= DATE_SUB(NOW(), INTERVAL $interval) GROUP BY recipe_id) rev ON r.id = rev.recipe_id
                ORDER BY (IFNULL(view_score, 0) + IFNULL(review_score, 0)) DESC LIMIT 50";
        $recipes = $this->db->fetchAll($query);
        foreach ($recipes as $key => $recipe) {
            if (!$this->auth->canViewObject($this->decoded, $recipe['author'], (int)$recipe['visibility'], true)) { unset($recipes[$key]); continue; }
            $recipes[$key]['author'] = ['name' => $this->userHelper->uuidToName($recipe['author']), 'username' => $this->userHelper->uuidToUsername($recipe['author'])];
            unset($recipes[$key]['view_score'], $recipes[$key]['review_score']);
        }
        return ["collection" => ["id" => $id, "author" => ["uuid" => "SYSTEM", "name" => "SYSTEM", "username" => "SYSTEM"], "slug" => $slug, "name" => $name, "description" => $desc, "featured" => 1, "visibility" => 3], "recipes" => array_slice(array_values($recipes), 0, 10)];
    }
}