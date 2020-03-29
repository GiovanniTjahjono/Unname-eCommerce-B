<?php

namespace App\Http\Controllers;

use App\Order;
use App\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //


    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
         //validation
         $request->validate([
            'id_produk' => 'required',
            'id_pembeli' => 'required',
            'stok_pembelian' => 'required',
            'nama_pembeli' => 'required',
            'alamat_pengiriman' => 'required',
            'nama_kota' => 'required',
            'tipeService' => 'required',
            'hargaTotalnya' => 'required'
        ]);
        Order::create([
            'kode_transaksi' => 'JAHSM78799', 
            'kuantitas' => $request->stok_pembelian, 
            'total_harga' => $request->hargaTotalnya, 
            'kurir' => 'Tiki', 
            'service' => $request->tipeService, 
            'ongkir' => 0, 
            'tanggal_penjualan' => Carbon::now()->format('Y-m-d H:i:s'),  
            'status_order' => 'menunggu', 
            'id_user' => $request->id_pembeli, 
            'id_produk' => $request->id_produk
        ]);
        $produk_stok = DB::table('products')
        ->where('id', $request->id_produk)
        ->get()->stok;

        //$id = DB::table('products')->orderBy('id', 'desc')->first()->id + 1;
        
        $stok_baru = $produk_stok->stok - $request->stok_pembelian;

        DB::table('products')
        ->where('id', $request->id_produk)
        ->update(['stok' => $stok_baru]);
        //cara 3
        //Product::create($request->all());//all akan mengambil semua data fillable yang ada di model product
        $kategoris = DB::table('kategoris')->get();

        $produks = DB::table('products')
            ->join('users', 'users.id', '=', 'products.id_user')
            ->join('kategoris', 'products.id_kategori', '=', 'kategoris.id')
            ->select('products.*', 'users.name', 'kategoris.nama_kategori')
            ->latest()->take(8)->get();

        return view('pages.home', compact('produks', 'kategoris'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Product $product)
    {
        //return view('pages.order', compact('product'));
    }
    public function showProduk(Product $product)
    {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api.rajaongkir.com/starter/city",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: 20abcef3dbf0bc2149a7412bc9b60005"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);
       
        $kategori = DB::table('kategoris')->where('id', '=', $product->id_kategori)->get();
        return view('pages.order.order', compact('product', 'kategori', 'response'));
    }
    public function RajaOngkir(Request $request)
    {
        
        $nama_kota_asal_pengiriman = $request['asal'];
        
        $curl1 = curl_init();
        curl_setopt_array($curl1, array(
            CURLOPT_URL => "http://api.rajaongkir.com/starter/city",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: 20abcef3dbf0bc2149a7412bc9b60005"
            ),
        ));

        $response1 = curl_exec($curl1);
        $err1 = curl_error($curl1);

        curl_close($curl1);
        $id_kab = '';
        $data = json_decode($response1, true);
        for ($i=0; $i < count($data['rajaongkir']['results']); $i++) { 
            if($data['rajaongkir']['results'][$i]['city_name'] === $nama_kota_asal_pengiriman){
                $id_kab = $data['rajaongkir']['results'][$i]['city_id'];
            }
        }       
                          
        Log::debug($request['kab_id']);
        Log::debug($id_kab);
        $asal = $id_kab;
        $id_kabupaten = $request['kab_id'];
        $kurir = 'tiki'; //$request['kurir'];
        $berat = 1;

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "http://api.rajaongkir.com/starter/cost",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "origin=" . $asal . "&destination=" . $id_kabupaten . "&weight=" . $berat . "&courier=" . $kurir . "",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
                "key: 20abcef3dbf0bc2149a7412bc9b60005"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {

            return $response;
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        //
    }
}