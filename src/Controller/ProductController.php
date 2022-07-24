<?php

namespace App\Controller;

use App\Entity\Image;
use App\Entity\Product;
use App\Form\ProductType;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use ContainerVdliMXW\PaginatorInterface_82dac15;
use LimitIterator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    /**
     * @Route("/product", name="app_product")
     */
    public function index(Request $request, ManagerRegistry $doctrine, PaginatorInterface $paginator): Response
    {
        $products = $doctrine->getRepository(Product::class)->findAll();

        $products = $paginator->paginate(
            $products,
           $request->query->getInt('page', 1),
            6
        );

        return $this->render('product/index.html.twig', [
            'products' => $products,
           
        ]);
    }

    /**
     * @Route("/product/add", name="add_product")
     * @Route("/product/update/{id}", name="update_product")
     */
    public function add(ManagerRegistry $doctrine, Product $product=null, Request $request):Response
    {

        if (!$product) {
            $product = new Product();
        }

        $entityManager = $doctrine->getManager();
        $form = $this->createForm(ProductType::class, $product);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()) {

            //On récupere les images transmises
            $images = $form->get('images')->getData();

            //On boucle sur les images
            foreach($images as $image){
                //On génere un nouveau nom de fichier
                $fichier = md5(uniqid()).'.'.$image->guessExtension();
                
                //On copie le fichier dans le dossier uploads
                $image->move(
                    $this->getParameter('images_directory'),
                    $fichier
                );

                //On stocke l'image dans la base de donnés(son nom)
                $img = new Image();
                $img->setUrl($fichier);
                $product->addImage($img);
            }
            $product = $form->getData();
            $entityManager->persist($product);
            $entityManager->flush();

            return $this->redirectToRoute('app_product');
        }
        return $this->render('product/add.html.twig', [
            'formProduct' => $form->createView(),
            'updateProduct' => $product->getId(),
            'product'=>$product
        ]);
    }

    /**
     * @Route("/delete/image/{id}", name="product_delete_image", methods={"DELETE", "GET"})
     */
    public function deleteImage(ManagerRegistry $doctrine,Image $image,Request $request)
    {
        $data = json_decode($request->getContent(),true);
        
        //On verifie si le token est valide
        // if($this->isCsrfTokenValid('delete',$image->getId(),isset($data['_token']))){
            //On recupere le nom de l'image
            $nom = $image->getUrl();
            //On supprime le fichier 
            unlink($this->getParameter('images_directory').'/'.$nom);

            //On supprime l'entree de la base 
            $em = $doctrine->getManager();
            $em->remove($image);
            $em->flush();

            //On repond en Json
            return new JsonResponse(['success'=>1]);
        // }else{
        //     return new JsonResponse(['error'=>'Token Invalide'],400);
        // }
    }
   
    /**
     * @Route("/product/{id}", name="show_product")
     */
    public function show(Product $product) : Response
    {   

        return $this->render('product/show.html.twig', [
            'product' => $product,
        ]);
    }
}
