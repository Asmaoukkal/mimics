<?php

namespace App\Controller;

use PDO;
use App\Entity\Product;
use App\Entity\Category;
use App\Form\ProductFormType;
use App\Form\CategoryFormType;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function admin(): Response
    {
        return $this->render('admin/index.html.twig', []);
    }

    #[Route('/admin/products', name: 'app_admin_products')]
    #[Route('/admin/products/update/{id}', name: 'app_admin_products_update')]
    public function adminProducts(?Product $product, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        // ?Product $product : le ? veut dire que par défault $product a une valeur null
        // dump($product);

        // 1er route '/admin/products'

        // Si la variable $product N'EST PAS (!), si elle renvoie false, cela veut dire qu'aucun id product n'est passé dans l'URL, alors on entre dans la condition, et on initialise un objet Entity $product, donc c'est une insertion de produit 



        // 2ème route : '/admin/products/update/{id}'

        // On envoi un id $product dans l'URL, Symfony comprend que l'on a besoin d'un objet entity product issu de la table SQL product, il est capable automatiquement d'aller sélectionner en BDD le produit et de l'envoyer en arguement de la fonction ?Product $product, à ce moment là, la variable $product contient les données du produit que l'on souhaite modifié, alors on ne rentre pas dans la condition pas dans la condition if

        if (!$product)
            $product = new Product;


        $form = $this->createForm(ProductFormType::class, $product);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $pictureFile = $form->get('picture')->getData();
            // dump($pictureFile);

            if ($pictureFile) {
                // retoutrne le nom du fichier d'origine  (sans l'extension)
                $originalFileName = pathinfo($pictureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // dump($originalFileName);

                // slug() sécurise le nom du fichier  (suppression espace etc...)
                $safeFileName = $slugger->slug($originalFileName);
                // dump($safeFileName);

                // On renomme l'image
                //                  p2-4457842511.png
                $newFileName = $safeFileName . '-' . uniqid() . '.' . $pictureFile->guessExtension();
                // dump($newFileName);
                // dump($this->getParameter('image_directory'));
                $currentPath = $this->getParameter('image_directory');

                try {
                    $pictureFile->move($currentPath, $newFileName);
                } catch (FileException $e) {
                    // dump($e->getMessage());
                }

                $product->setPicture($newFileName);
                dump($product);
            }

            if($product->getId()){

                $messageValidate = "Les modifications on été enregistrées.";

            }else{

                // Sinon dans tout les autres cas, c'est une insertion

                $messageValidate = "L'article à été enregistré";

            }

            $product->setCreatedAt(new \DateTimeImmutable());
            $entityManager->persist($product);
            $entityManager->flush();

            $this->addFlash('success', $messageValidate);

            return $this->redirectToRoute('app_admin_products');
        }

        // $repoProduct : objet issu de la class ProductRepository
        $repoProduct = $entityManager->getRepository(Product::class);
        $dbProduct = $repoProduct->findAll();
        // dump($dbProduct);

        return $this->render('admin/products.html.twig', [
            'productForm' => $form,
            'dbProduct' => $dbProduct,
            'pictureFile' => $product->getPicture()
        ]);
    }

    #[Route('/admin/products/remove/{id}', name: 'app_admin_products_remove')]
    public function adminProductsRemove($id, ProductRepository $repoProduct, EntityManagerInterface $entityManager, Product $product)
    {

       $product = $repoProduct->find($id);
       dump($product);
       
        $entityManager->remove($product);
        $entityManager->flush();

        $this->addFlash('success', "Le produit a été supprimé.");

        return $this->redirectToRoute('app_admin_products');
        
    }

    #[Route('/admin/category', name: 'app_admin_category')]
    public function adminCategory(Request $request, EntityManagerInterface $entityManager, CategoryRepository $repoCategory): Response
    {
        $category = new Category;

        $form = $this->createForm(CategoryFormType::class, $category);

        // $category->setTitle($_POST['title']);
        // $category->setTitle($_POST['descritpion']);
        $form->handleRequest($request);

        // if(isset($_POST['submit']) && $_SERVER['REQUEST_METHOD'] === 'POST')
        if ($form->isSubmitted() && $form->isValid()) {

            $category->setCreatedAt(new \DateTimeImmutable());

            // $stmt->prepare("INSERT INTO category VALUES (:title)")
            // $stmt->bindValue(':title', $category->getTitle(), PDO::PARAM_STR);
            $entityManager->persist($category);
              
            // $stmt->execute();
            $entityManager->flush();

            // Message utilisateur stockés en session
            // $_SESSION['msgValidate'] = "La catégorie a été enregistrée."
            // $_SESSION['success'] = "La catégorie a été enregistrée."
            $this->addFlash('success', "La catégorie a été enregistrée.");

            return $this->redirectToRoute('app_admin_category');
        }

        /*
            $data = $connect_db->query("SELECT * FROM category");
            $dbCategory = $data->fetchAll(PDO::FETCH_ASSOC);

            Un classe Repository contient des méthodes permettant uniquement d'executer des requêtes de sélections (SELECT) en BDD  (find($id), findAll(), findBy(), findOneBy())
        */
        $dbCategory = $repoCategory->findAll();
        dump($dbCategory);

        return $this->render('admin/category.html.twig', [
            'categoryForm' => $form,
            'dbCategory' => $dbCategory
        ]);       
    }

          

    //                              1
    #[Route('/admin/category/update/{id}', name: 'app_admin_category_update')]
    public function adminCategoryUpdate($id, Category $category, Request $request, EntityManagerInterface $entityManager, CategoryRepository $repoCategory): Response
    {
        // dump($category);

        // SELECT * FROM category WHERE id = $id; // 1
        // + fetch(PDO::FETCH_ASSOC)
        $category = $repoCategory->find($id);
        // dump($id);
        // dump($category);

        $form = $this->createForm(CategoryFormType::class, $category);

        // $category->setTitle($_POST['title'])
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // UPDATE category SET title = $category->geTitle(), description = $category->getDescription WHERE id = $id
            $entityManager->persist($category);
            $entityManager->flush();

            dump($category->getTitle());
            $categoryTitle = $category->getTitle();

            $this->addFlash('success', "La catégorie <strong class='text-white'>$categoryTitle</strong> a été modifiée.");

            return $this->redirectToRoute('app_admin_category');
        }

        $dbCategory = $repoCategory->findAll();

        return $this->render('admin/category.html.twig', [
            'categoryForm' => $form,
            'dbCategory' => $dbCategory
        ]);
    }

    #[Route('/admin/category/remove/{id}', name: 'app_admin_category_remove')]
    public function adminCategoryRemove($id, EntityManagerInterface $entityManager, CategoryRepository $repoCategory)
    {
        $category = $repoCategory->find($id);
        // dump($category);
        dump($category->getProducts()->isEmpty());

        // $categoryTitle =

        // DELETE FROM category WHERE id = $id
       if($category->getProducts()->isEmpty()){
            
        $entityManager->remove($category);
        $entityManager->flush();

        $this->addFlash('success', "La catégorie a été supprimée.");
       }else{

       $this->addFlash('danger', "La catégorie ne peut pas être supprimée car elle contient des produits.");

       }

        return $this->redirectToRoute('app_admin_category');
    
    }

    #[Route('/admin/orders', name: 'app_admin_orders')]
    public function adminOrders(): Response
    {
        return $this->render('admin/orders.html.twig', []);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function adminUsers(): Response
    {
        return $this->render('admin/users.html.twig', []);
    }
}