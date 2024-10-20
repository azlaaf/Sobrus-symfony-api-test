<?php

namespace App\Tests\Functional;

use App\Entity\BlogArticle;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class BlogArticleControllerTest extends WebTestCase
{
    private $client;

    protected function setUp(): void
    {
        // Créez un client pour les tests
        $this->client = static::createClient();
    }

    public function testCreateBlogArticle(): void
    {
        $articleData = [
            'title' => 'Test Article',
            'content' => 'This is a test article.',
            'publicationDate' => '2024-10-20 10:00:00',
            'keywords' => ['test', 'article'],
            'status' => 'draft',
            'slug' => 'test-article',
            'coverPictureRef' => null,
        ];

        $this->client->request('POST', '/blog/articles', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($articleData));

        // Vérifiez que la réponse est 201 Created
        $this->assertResponseStatusCodeSame(Response::HTTP_CREATED);

        // Vérifiez que les données de l'article sont présentes dans la réponse
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertArrayHasKey('id', $responseData);
        $this->assertEquals($articleData['title'], $responseData['title']);
    }

    public function testGetBlogArticles(): void
    {
        $this->client->request('GET', '/blog/articles');

        // Vérifiez que la réponse est 200 OK
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifiez que la réponse contient un tableau
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
    }

    public function testGetBlogArticle(): void
    {
        // Créez un article dans la base de données
        $entityManager = self::$container->get('doctrine')->getManager();
        $article = new BlogArticle();
        $article->setTitle('Sample Article');
        $article->setContent('This is a sample article.');
        $article->setPublicationDate(new \DateTime('2024-10-20 10:00:00'));
        $article->setCreationDate(new \DateTime());
        $article->setKeywords(['sample']);
        $article->setStatus('draft');
        $article->setSlug('sample-article');

        $entityManager->persist($article);
        $entityManager->flush();

        $this->client->request('GET', '/blog/articles/' . $article->getId());

        // Vérifiez que la réponse est 200 OK
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifiez que les données de l'article sont présentes dans la réponse
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Sample Article', $responseData['title']);
    }

    public function testUpdateBlogArticle(): void
    {
        // Créez un article dans la base de données
        $entityManager = self::$container->get('doctrine')->getManager();
        $article = new BlogArticle();
        $article->setTitle('Old Title');
        $article->setContent('Old content.');
        $entityManager->persist($article);
        $entityManager->flush();

        $updateData = [
            'title' => 'Updated Title',
            'content' => 'Updated content.',
            'status' => 'published',
        ];

        $this->client->request('PATCH', '/blog/articles/' . $article->getId(), [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($updateData));

        // Vérifiez que la réponse est 200 OK
        $this->assertResponseStatusCodeSame(Response::HTTP_OK);

        // Vérifiez que les données mises à jour de l'article sont présentes dans la réponse
        $responseData = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertEquals('Updated Title', $responseData['title']);
    }

    public function testDeleteBlogArticle(): void
    {
        // Créez un article dans la base de données
        $entityManager = self::$container->get('doctrine')->getManager();
        $article = new BlogArticle();
        $article->setTitle('To Be Deleted');
        $entityManager->persist($article);
        $entityManager->flush();

        $this->client->request('DELETE', '/blog/articles/' . $article->getId());

        // Vérifiez que la réponse est 204 No Content
        $this->assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);

        // Vérifiez que l'article a bien été supprimé
        $deletedArticle = $entityManager->getRepository(BlogArticle::class)->find($article->getId());
        $this->assertNull($deletedArticle);
    }
}
