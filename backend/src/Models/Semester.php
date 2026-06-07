<?php
/**
 * 学期模型
 */

namespace App\Models;

class Semester extends BaseModel
{
    protected string $table = 'semesters';
    
    /**
     * 获取所有启用的学期（用于下拉选择）
     */
    public function getAllActive(): array
    {
        $sql = "SELECT id, name, start_date, end_date, status, sort_order 
                FROM {$this->table} 
                WHERE status = 1 
                ORDER BY sort_order DESC, name DESC";
        return $this->query($sql);
    }
    
    /**
     * 获取所有学期名称列表
     */
    public function getSemesterNames(): array
    {
        $sql = "SELECT name FROM {$this->table} WHERE status = 1 ORDER BY sort_order DESC, name DESC";
        $result = $this->query($sql);
        return array_column($result, 'name');
    }
    
    /**
     * 根据名称查找
     */
    public function findByName(string $name): ?array
    {
        return $this->findBy('name', $name);
    }
    
    /**
     * 分页查询带搜索
     */
    public function search(int $page, int $pageSize, ?string $keyword = null, ?int $status = null): array
    {
        $where = ['1=1'];
        $params = [];
        
        if ($keyword) {
            $where[] = "name LIKE ?";
            $params[] = "%{$keyword}%";
        }
        
        if ($status !== null) {
            $where[] = "status = ?";
            $params[] = $status;
        }
        
        $whereStr = implode(' AND ', $where);
        $offset = ($page - 1) * $pageSize;
        
        $sql = "SELECT * FROM {$this->table} WHERE {$whereStr} ORDER BY sort_order DESC, name DESC LIMIT {$pageSize} OFFSET {$offset}";
        $items = $this->query($sql, $params);
        
        $countSql = "SELECT COUNT(*) as count FROM {$this->table} WHERE {$whereStr}";
        $countResult = $this->query($countSql, $params);
        $total = (int) $countResult[0]['count'];
        
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'pageSize' => $pageSize
        ];
    }
}
