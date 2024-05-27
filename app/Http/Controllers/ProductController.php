<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class ProductController extends Controller
{
    public function index()
    {
        $client = new Client();
        $response = $client->request('GET', 'https://fakestoreapi.com/products');
        $products = json_decode($response->getBody());

        return response()->json($products);
    }
}
