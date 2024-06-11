<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;

class BasketballController extends Controller
{
    protected $database;
    protected $httpClient;

    public function __construct()
    {
        Log::info('BasketballController constructor called');

        $credentials = config('firebase.credentials');
        $databaseUrl = config('firebase.database.url');

        if (!is_string($credentials) || !is_string($databaseUrl)) {
            Log::error('Firebase credentials or database URL not set correctly.');
            throw new \Exception('Firebase credentials or database URL not set correctly.');
        }

        if (!file_exists(base_path($credentials))) {
            Log::error('Firebase credentials file does not exist: ' . base_path($credentials));
            throw new \Exception('Firebase credentials file does not exist: ' . base_path($credentials));
        }

        try {
            $firebase = (new Factory)
                ->withServiceAccount(base_path($credentials))
                ->withDatabaseUri($databaseUrl);

            Log::info('Firebase Factory initialized');

            $this->database = $firebase->createDatabase();
            Log::info('Firebase Database created');

            $this->httpClient = new Client();
        } catch (\Exception $e) {
            Log::error('Error initializing Firebase: ' . $e->getMessage());
            throw $e;
        }
    }


    public function index(Request $request)
    {
        Log::info('index method called');
    
        try {
            $search = $request->query('search');
    
            $players = $this->database->getReference('players')->getValue();
            if (is_null($players)) {
                $players = [];
            } else {
                $players = array_map(function ($player, $key) {
                    $player['id'] = $key;
                    return $player;
                }, $players, array_keys($players));
            }
    
            if ($search) {
                $players = array_filter($players, function ($player) use ($search) {
                    return stripos($player['name'], $search) !== false ||
                        stripos($player['team'], $search) !== false ||
                        stripos($player['position'], $search) !== false;
                });
            }
    
            return response()->json(array_values($players));
        } catch (\Exception $e) {
            Log::error('Error in index method: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    

    public function store(Request $request)
    {
        Log::info('store method called');
    
        try {
            $playerData = $request->all();
            $playerData['name'] = strtolower($playerData['name']);
            $playerData['team'] = strtolower($playerData['team']);
            $playerData['position'] = strtolower($playerData['position']);
            $playerRef = $this->database->getReference('players')->push($playerData);
            return response()->json($playerRef->getValue());
        } catch (\Exception $e) {
            Log::error('Error in store method: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
    


    public function show($id)
    {
        Log::info('show method called');

        try {
            $player = $this->database->getReference("players/{$id}")->getValue();

            if ($player) {
                // Enrich player data with Wikipedia information
                $wikipediaData = $this->fetchWikipediaData($player['name']);
                $player['wikipedia'] = $wikipediaData;
            }

            return response()->json($player);
        } catch (\Exception $e) {
            Log::error('Error in show method: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info('update method called');

        try {
            $playerData = $request->all();
            $playerData['name'] = strtolower($playerData['name']);
            $playerData['team'] = strtolower($playerData['team']);
            $playerData['position'] = strtolower($playerData['position']);
            $this->database->getReference("players/{$id}")->update($playerData);
            return response()->json(['status' => 'success', 'message' => 'Player updated successfully']);
        } catch (\Exception $e) {
            Log::error('Error in update method: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }



    public function destroy($id)
    {
        Log::info('destroy method called');

        try {
            $this->database->getReference("players/{$id}")->remove();
            return response()->json('Player deleted successfully');
        } catch (\Exception $e) {
            Log::error('Error in destroy method: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    private function fetchWikipediaData($playerName)
    {
        // Capitalize the first letter of each word
        $formattedName = ucwords(strtolower($playerName));
        $url = "https://en.wikipedia.org/w/api.php?action=query&prop=extracts&format=json&exintro=&titles=" . urlencode($formattedName);
    
        try {
            $response = $this->httpClient->get($url);
            $data = json_decode($response->getBody()->getContents(), true);
    
            $pages = $data['query']['pages'];
            $page = reset($pages);
    
            return $page['extract'] ?? 'No information available';
        } catch (\Exception $e) {
            Log::error('Error fetching Wikipedia data: ' . $e->getMessage());
            return 'Error fetching information';
        }
    }
    
}
