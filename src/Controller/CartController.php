<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\ProductRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

final class CartController extends AbstractController
{
    #[Route('/cart', name: 'app_cart')]
    public function index(SessionInterface $session,ProductRepository $repoProduct): Response
    {
        $cart = $session->get('cart', []);
        dump($cart);
        //on recupere le panier dans la session
        $dataCart = [];
        $total = 0;
           //on initialise le tableau qui va contenir les produits du panier
           
if (!empty($cart)){
        foreach($cart as $id => $quantity){
                 dump($id);
                dump($quantity);
       //on recupere le produit en fonction de l'id
            $product = $repoProduct->find($id);
      //on ajoute dans le tableau dataCart les produits du panier
            $dataCart[] = [
                'product' => $product,//on envoi l'objet entity produite directement dans l'array
                'quantity' => $quantity
                
         ];
            $total += $product->getPrice() * $quantity;
        }
    }
        dump($dataCart);
        dump($total);

        return $this->render('cart/index.html.twig', [

            'dataCart' => $dataCart,
            'total' => $total
        ]);

    }


    #[Route('/cart/add/{id}', name: 'app_cart_add')]
    public function cartAdd(Request $request,Product $product, SessionInterface $session)
    {
       // dump($product);
     //   dump($request);
       

        // return $this->render('app_cart_add', []);

        //creation du panier dans la session
        $cart = $session->get('cart', []);
        $id = $product->getId();
        $quantity = $request->request->get('quantity');

        /**
         $idProduit => quantity
         */

        //  dump($cart);
        //  dump( $id);
        //  dump( $quantity);


         if(!empty($cart[$id])){
             $cart[$id] = $cart[$id] + $quantity;
             //dump('if product exist dans le panier');
         }else{ 
            //dump('else product inexist dans le panier');
                 $cart[$id] = $quantity;
            }

            $session->set('cart', $cart);
            // $session->remove('cart');

        
            //dump($cart);
          return $this->redirectToRoute('app_cart');
    } 
}
