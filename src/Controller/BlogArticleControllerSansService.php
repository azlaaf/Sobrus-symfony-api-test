
namespace App\Controller;

use App\Entity\BlogArticle;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class BlogArticleController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/blog/articles', name: 'create_blog_article', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Validate the input data
        if (empty($data['title']) || empty($data['content'])) {
            return $this->json(['error' => 'Title and content are required.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Validate keywords
        if (!is_array($data['keywords'])) {
            return $this->json(['error' => 'Keywords must be an array.'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Validate status
        $validStatuses = ['draft', 'published', 'deleted'];
        if (!in_array($data['status'], $validStatuses)) {
            return $this->json(['error' => 'Status must be one of: ' . implode(', ', $validStatuses)], JsonResponse::HTTP_BAD_REQUEST);
        }

        $user = (object) ['id' => 1];
        // this  tAuthorId i dont know if its coming from a LINK BETWEEN table  so i just passt it  with a static number its not pratical but i dont have the information
        $blogArticle = new BlogArticle();
        $blogArticle->setAuthorId($user->id);
        $blogArticle->setTitle($data['title']);
        $blogArticle->setPublicationDate(new \DateTime($data['publicationDate'])); // Assurez-vous que publicationDate est fourni
        $blogArticle->setCreationDate(new \DateTime());
        $blogArticle->setContent($data['content']);
        $blogArticle->setKeywords($data['keywords']);
        $blogArticle->setStatus($data['status']);
        $blogArticle->setSlug($data['slug']);
        $blogArticle->setCoverPictureRef($this->handleFileUpload($data['coverPictureRef'])); // Gestion de l'upload

        $this->entityManager->persist($blogArticle);
        $this->entityManager->flush();

        return $this->json($blogArticle, JsonResponse::HTTP_CREATED);
    }

    #[Route('/blog/articles', name: 'get_blog_articles', methods: ['GET'])]
public function index(): JsonResponse
{
    $articles = $this->entityManager->getRepository(BlogArticle::class)->findAll();

    $data = [];
    foreach ($articles as $article) {
        $data[] = [
            'AuthorId' => $article->getAuthorId(),
            'title' => $article->getTitle(),
            'content' => $article->getContent(),
            'publicationDate' => $article->getPublicationDate()->format('Y-m-d H:i:s'),
            'creationDate' => $article->getCreationDate()->format('Y-m-d H:i:s'),
            'keywords' => $article->getKeywords(),
            'status' => $article->getStatus(),
            'slug' => $article->getSlug(),
            'coverPictureRef' => $article->getCoverPictureRef(),
        ];
    }

    return $this->json($data);
}


#[Route('/blog/articles/{id}', name: 'get_blog_article', methods: ['GET'])]
public function show(int $id): JsonResponse
{
    $article = $this->entityManager->getRepository(BlogArticle::class)->find($id);
    if (!$article) {
        return $this->json(['error' => 'Article not found.'], JsonResponse::HTTP_NOT_FOUND);
    }

    return $this->json([
        'AuthorId' => $article->getAuthorId(),
        'title' => $article->getTitle(),
        'content' => $article->getContent(),
        'publicationDate' => $article->getPublicationDate()->format('Y-m-d H:i:s'),
        'creationDate' => $article->getCreationDate()->format('Y-m-d H:i:s'),
        'keywords' => $article->getKeywords(),
        'status' => $article->getStatus(),
        'slug' => $article->getSlug(),
        'coverPictureRef' => $article->getCoverPictureRef(),
    ]);
}


#[Route('/blog/articles/{id}', name: 'update_blog_article', methods: ['PATCH'])]
public function update(Request $request, int $id): JsonResponse
{
    $article = $this->entityManager->getRepository(BlogArticle::class)->find($id);

    if (!$article) {
        return $this->json(['error' => 'Article not found.'], JsonResponse::HTTP_NOT_FOUND);
    }

    $data = json_decode($request->getContent(), true);

    if (isset($data['title']) && !empty($data['title'])) {
        $article->setTitle($data['title']);
    }

    if (isset($data['content']) && !empty($data['content'])) {
        $article->setContent($data['content']);
    }

    if (isset($data['keywords'])) {
        if (!is_array($data['keywords'])) {
            return $this->json(['error' => 'Keywords must be an array.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $article->setKeywords($data['keywords']);
    }

    if (isset($data['status'])) {
        $validStatuses = ['draft', 'published', 'deleted'];
        if (!in_array($data['status'], $validStatuses)) {
            return $this->json(['error' => 'Invalid status.'], JsonResponse::HTTP_BAD_REQUEST);
        }
        $article->setStatus($data['status']);
    }

    if (isset($data['coverPictureRef'])) {
        $article->setCoverPictureRef($this->handleFileUpload($data['coverPictureRef']));
    }

    if (isset($data['publicationDate'])) {
        try {
            $article->setPublicationDate(new \DateTime($data['publicationDate']));
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format.'], JsonResponse::HTTP_BAD_REQUEST);
        }
    }

    $this->entityManager->flush();

    return $this->json([
        'id' => $article->getId(),
        'title' => $article->getTitle(),
        'content' => $article->getContent(),
        'publicationDate' => $article->getPublicationDate()?->format('Y-m-d H:i:s'),
        'creationDate' => $article->getCreationDate()?->format('Y-m-d H:i:s'),
        'keywords' => $article->getKeywords(),
        'status' => $article->getStatus(),
        'slug' => $article->getSlug(),
        'coverPictureRef' => $article->getCoverPictureRef(),
    ]);
}


    #[Route('/blog/articles/{id}', name: 'delete_blog_article', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $article = $this->entityManager->getRepository(BlogArticle::class)->find($id);
        if (!$article) {
            return $this->json(['error' => 'Article not found.'], JsonResponse::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($article);
        $this->entityManager->flush();

        return $this->json(['message' => 'Article deleted.'], JsonResponse::HTTP_NO_CONTENT);
    }


    private function handleFileUpload($file): string
    {
        // J'ai besoin d'un twig html  pour faire sa //
        if (!$file || !is_uploaded_file($file)) {
            throw new \Exception('Invalid file upload.');
        }

        $uploadDir = __DIR__ . '/../../public/uploaded_pictures/';
        $fileName = uniqid() . '_' . basename($file);
        $targetPath = $uploadDir . $fileName;

        if (!move_uploaded_file($file, $targetPath)) {
            throw new \Exception('Failed to upload file.');
        }

        return '/uploaded_pictures/' . $fileName;
    }

}
