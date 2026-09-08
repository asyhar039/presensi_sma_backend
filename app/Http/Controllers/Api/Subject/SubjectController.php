<?php

namespace App\Http\Controllers\Api\Subject;

use App\Http\Controllers\Controller;
use App\Http\Requests\Subject\StoreSubjectRequest;
use App\Http\Requests\Subject\UpdateSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use App\Services\Subject\SubjectService;
use App\Traits\ApiResponseTrait;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

#[Group('Subjects', weight: 4)]
class SubjectController extends Controller
{
    use ApiResponseTrait;

    public function __construct(private SubjectService $subjectService) {}

    /**
     * List subjects with pagination.
     */
    #[Endpoint(title: 'List subjects', description: 'Returns paginated subjects searchable by name; sort by allowed columns.')]
    #[QueryParameter('page', description: 'Current page number.', type: 'int', default: 1)]
    #[QueryParameter('per_page', description: 'Items per page (max 50).', type: 'int', default: 10)]
    #[QueryParameter('search', description: 'Search by subject name.', type: 'string')]
    #[QueryParameter('sortBy', description: 'Sort column: id, name, created_at.', type: 'string')]
    #[QueryParameter('order', description: 'Sort direction: asc or desc.', type: 'string')]
    public function index(Request $request): JsonResponse
    {
        $paginator = $this->subjectService->paginate($request);

        return $this->paginatedResponse(
            $paginator,
            SubjectResource::collection($paginator->items()),
            'Subjects retrieved successfully.'
        );
    }

    /**
     * Create a new subject.
     */
    #[Endpoint(title: 'Create subject', description: 'Creates a subject with a unique name.')]
    public function store(StoreSubjectRequest $request): JsonResponse
    {
        $subject = $this->subjectService->create($request->validated());

        return $this->successResponse(
            data: SubjectResource::make($subject),
            message: 'Subject created successfully.',
            code: Response::HTTP_CREATED
        );
    }

    /**
     * Show a single subject.
     */
    #[Endpoint(title: 'Show subject', description: 'Returns a single subject by id.')]
    public function show(Subject $subject): JsonResponse
    {
        return $this->successResponse(
            data: SubjectResource::make($subject),
            message: 'Subject retrieved successfully.'
        );
    }

    /**
     * Update a subject.
     */
    #[Endpoint(title: 'Update subject', description: 'Updates a subject name keeping uniqueness.')]
    public function update(UpdateSubjectRequest $request, Subject $subject): JsonResponse
    {
        $subject = $this->subjectService->update($subject, $request->validated());

        return $this->successResponse(
            data: SubjectResource::make($subject),
            message: 'Subject updated successfully.'
        );
    }

    /**
     * Delete a subject.
     */
    #[Endpoint(title: 'Delete subject', description: 'Deletes a subject by id.')]
    public function destroy(Subject $subject): JsonResponse
    {
        $this->subjectService->delete($subject);

        return $this->successResponse(message: 'Subject deleted successfully.');
    }
}
