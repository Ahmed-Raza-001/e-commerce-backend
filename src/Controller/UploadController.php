<?php
namespace App\Controller;
use App\Service\SupabaseStorageService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
class UploadController extends AbstractController
{
    private SupabaseStorageService $storageService;

    public function __construct(SupabaseStorageService $storageService)
    {
        $this->storageService = $storageService;
    }

    #[Route('/upload', name: 'api_upload', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function upload(Request $request): JsonResponse
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $file */
        $file = $request->files->get('file') ?? $request->files->get('image');

        if (!$file) {
            return $this->json([
                'message' => 'No file was provided. Please send a file using the "file" or "image" key in multipart/form-data.'
            ], Response::HTTP_BAD_REQUEST);
        }

        $folder = $request->request->get('folder', 'product');
        $driver = $request->request->get('driver', 'supabase');

        try {
            if ($driver === 'local') {
                $result = $this->storageService->uploadLocal($file, $folder);
            } else {
                $result = $this->storageService->upload($file, $folder);
            }

            return $this->json([
                'message' => 'File uploaded successfully.',
                'data' => $result
            ], Response::HTTP_CREATED);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'message' => 'Validation error.',
                'error' => $e->getMessage()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return $this->json([
                'message' => 'Upload failed.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
