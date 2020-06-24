<?php


namespace App\Http\Controllers;


use Illuminate\Http\Request;
use TopRadar\Service\CheckXml;

class TestController extends Controller
{
	public function handle(Request $request)
	{
		$foo = new CheckXml(
			$request->get('newFeed'),
			$request->get('oldFeed'),
			$request->get('offerTemplate'));

		$foo->check();
	}
}