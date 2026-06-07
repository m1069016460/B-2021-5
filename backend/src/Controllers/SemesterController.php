<?php
/**
 * 学期管理控制器
 */

namespace App\Controllers;

use App\Models\Semester;
use App\Models\Course;
use App\Models\Grade;
use App\Utils\Response;
use App\Utils\Validator;
use App\Utils\Logger;

class SemesterController
{
    private Semester $semesterModel;
    private Course $courseModel;
    private Grade $gradeModel;
    
    public function __construct()
    {
        $this->semesterModel = new Semester();
        $this->courseModel = new Course();
        $this->gradeModel = new Grade();
    }
    
    /**
     * 获取学期列表
     */
    public function index(array $params): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $pageSize = (int) ($_GET['pageSize'] ?? 20);
        $keyword = $_GET['keyword'] ?? null;
        $status = isset($_GET['status']) ? (int) $_GET['status'] : null;
        
        $result = $this->semesterModel->search($page, $pageSize, $keyword, $status);
        
        Response::success([
            'items' => $result['items'],
            'total' => $result['total'],
            'page' => $result['page'],
            'pageSize' => $result['pageSize'],
            'totalPages' => ceil($result['total'] / $result['pageSize'])
        ]);
    }
    
    /**
     * 获取所有启用的学期（下拉选择用）
     */
    public function all(array $params): void
    {
        $semesters = $this->semesterModel->getAllActive();
        $semesterNames = $this->semesterModel->getSemesterNames();
        
        Response::success([
            'semesters' => $semesters,
            'semesterNames' => $semesterNames
        ]);
    }
    
    /**
     * 获取学期详情
     */
    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $semester = $this->semesterModel->find($id);
        
        if (!$semester) {
            Response::error('学期不存在', 404);
            return;
        }
        
        Response::success($semester);
    }
    
    /**
     * 创建学期
     */
    public function store(array $params): void
    {
        $data = $this->getInput();
        
        $validator = new Validator($data);
        $validator->required('name', '学期名称不能为空')
                  ->regex('name', '/^\d{4}-\d{4}-\d$/', '学期格式不正确，应为：2024-2025-1');
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        if ($this->semesterModel->exists('name', $data['name'])) {
            Response::error('该学期已存在', 400);
            return;
        }
        
        $semesterId = $this->semesterModel->create([
            'name' => $data['name'],
            'start_date' => $data['startDate'] ?? null,
            'end_date' => $data['endDate'] ?? null,
            'status' => $data['status'] ?? 1,
            'sort_order' => $data['sortOrder'] ?? 0
        ]);
        
        Logger::info("Semester created: {$data['name']}");
        
        Response::success(['id' => $semesterId], '创建成功');
    }
    
    /**
     * 更新学期
     */
    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $semester = $this->semesterModel->find($id);
        
        if (!$semester) {
            Response::error('学期不存在', 404);
            return;
        }
        
        $data = $this->getInput();
        
        $validator = new Validator($data);
        if (isset($data['name'])) {
            $validator->regex('name', '/^\d{4}-\d{4}-\d$/', '学期格式不正确，应为：2024-2025-1');
        }
        
        if ($validator->fails()) {
            Response::error($validator->getFirstError(), 400);
            return;
        }
        
        if (isset($data['name']) && $this->semesterModel->exists('name', $data['name'], $id)) {
            Response::error('该学期已存在', 400);
            return;
        }
        
        $updateData = [];
        if (isset($data['name'])) $updateData['name'] = $data['name'];
        if (isset($data['startDate'])) $updateData['start_date'] = $data['startDate'];
        if (isset($data['endDate'])) $updateData['end_date'] = $data['endDate'];
        if (isset($data['status'])) $updateData['status'] = $data['status'];
        if (isset($data['sortOrder'])) $updateData['sort_order'] = $data['sortOrder'];
        
        if (!empty($updateData)) {
            $this->semesterModel->update($id, $updateData);
            Logger::info("Semester updated: ID {$id}");
        }
        
        Response::success(null, '更新成功');
    }
    
    /**
     * 删除学期
     */
    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $semester = $this->semesterModel->find($id);
        
        if (!$semester) {
            Response::error('学期不存在', 404);
            return;
        }
        
        $coursesCount = $this->courseModel->count(['semester' => $semester['name']]);
        $gradesCount = $this->gradeModel->count(['semester' => $semester['name']]);
        
        if ($coursesCount > 0 || $gradesCount > 0) {
            Response::error("该学期有 {$coursesCount} 门课程和 {$gradesCount} 条成绩记录，无法删除", 400);
            return;
        }
        
        $this->semesterModel->delete($id);
        
        Logger::info("Semester deleted: {$semester['name']}");
        
        Response::success(null, '删除成功');
    }
    
    private function getInput(): array
    {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?? [];
    }
}
