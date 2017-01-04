<?php
namespace Jeylabs\Recruiter;

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions as GuzzleRequestOptions;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class Recruiter
{
    const VERSION = '1.0.0';
    const CANDIDATE_API = 'api/recruitment-management/candidate';
    const VACANCY_API = 'api/recruitment-management/vacancy';
    const VACANCY_CATEGORY_API = 'api/recruitment-management/vacancy-category';
    const FILE_PATH = 'dd/recruiter/';
    const IMAGE_PATH = 'dd/image/vacancy/';
    const DEFAULT_TIMEOUT = 10;
    protected $client;
    protected $secret_key;
    protected $isAsyncRequest = false;
    protected $formParameters = [];
    protected $headers = [];
    protected $lastResponse;
    protected $candidateApiBabeUri;
    protected $promises = [];

    public function __construct($secret_key, $candidateApiBabeUri, $isAsyncRequest = false, $httpClient = null)
    {
        $this->secret_key = $secret_key;
        $this->candidateApiBabeUri = $candidateApiBabeUri;
        $this->isAsyncRequest = $isAsyncRequest;
        $this->client = $httpClient ?: new Client([
            'base_uri' => $this->candidateApiBabeUri,
            'timeout' => self::DEFAULT_TIMEOUT,
            'connect_timeout' => self::DEFAULT_TIMEOUT,
        ]);
    }

    public function isAsyncRequests()
    {
        return $this->isAsyncRequest;
    }

    public function setAsyncRequests($isAsyncRequest)
    {
        $this->isAsyncRequest = $isAsyncRequest;
        return $this;
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function setHeaders($headers = [])
    {
        $this->headers = $headers;
        return $this;
    }

    public function getFormParameter()
    {
        return $this->formParameters;
    }

    public function setFormParameter($formParameters = [])
    {
        $this->formParameters = $formParameters;
        return $this;
    }

    public function getLastResponse()
    {
        return $this->lastResponse;
    }

    protected function makeRequest($method, $uri, $query = [], $formParameters = [], $file = [])
    {
//        $options[GuzzleRequestOptions::FORM_PARAMS] = $formParameters;
        $options[GuzzleRequestOptions::QUERY] = $query;
        $options[GuzzleRequestOptions::HEADERS] = $this->getDefaultHeaders();
        $options[GuzzleRequestOptions::MULTIPART] = $file;

        if ($this->isAsyncRequest) {
            return $this->promises[] = $this->client->requestAsync($method, $uri, $options);
        }

        $this->lastResponse = $this->client->request($method, $uri, $options);
        return json_decode($this->lastResponse->getBody(), true);
    }

    protected function makeRequestForFile($method, $uri, $query = [], $formParameters = [], $file = [])
    {
        $options[GuzzleRequestOptions::FORM_PARAMS] = $formParameters;
        $options[GuzzleRequestOptions::QUERY] = $query;
        $options[GuzzleRequestOptions::HEADERS] = $this->getDefaultHeaders();

        if ($this->isAsyncRequest) {
            return $this->promises[] = $this->client->requestAsync($method, $uri, $options);
        }
        $this->lastResponse = $this->client->request($method, $uri, $options);
        $header = $this->lastResponse->getHeaders();
        $response = [
            'file'=>$this->lastResponse->getBody()->getContents(),
            'contentType' => $header['Content-Type']
        ];
        return $response;
    }

    protected function getDefaultHeaders()
    {
        return array_merge([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ], $this->headers);
    }

    protected function getDefaultFormParameter()
    {

        return array_merge([
            'Authorization' => 'Bearer ' . $this->secret_key,
        ], $this->formParameters);
    }

    public function getVacancyCategory($query = [], $formParameters = [], $file = null)
    {

        $this->setHeaders(['Content-type' => 'application/json']);
        $uri = self::VACANCY_CATEGORY_API;
        $vacancyCategory = $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        if ($vacancyCategory['message']['success']) {
            return $vacancyCategory['message']['results'];
        } else {
            return $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        }
    }

    public function filterVacancy($query = [], $formParameters = [], $file = null)
    {
        $this->setHeaders(['Content-type' => 'application/json']);
        $uri = self::VACANCY_API . '/filter';
        $vacancy = $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        if ($vacancy['message']['success']) {
            return $vacancy['message']['results'];
        } else {
            return $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        }
    }

    public function searchVacancy($search)
    {
        $query = [];
        $formParameters = [];
        $file = null;
        $this->setHeaders(['Content-type' => 'application/json']);
        $uri = self::VACANCY_API . '/search/' . $search;
        $vacancy = $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        if ($vacancy['message']['success']) {
            return $vacancy['message']['results'];
        } else {
            return $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        }
    }

    public function getVacancy($query = [], $formParameters = [], $file = null)
    {
        $this->setHeaders(['Content-type' => 'application/json']);
        $uri = self::VACANCY_API;
        $vacancy = $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        if ($vacancy['message']['success']) {
            return $vacancy['message']['results'];
        } else {
            return $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        }
    }

    public function showVacancy($id){
        $query = [];
        $formParameters = [];
        $file = null;
        $this->setHeaders(['Content-type' => 'application/json']);
        $uri = self::VACANCY_API.'/'.$id;
        $vacancy = $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        if ($vacancy['message']['success']) {
            return $vacancy['message']['results'];
        } else {
            return $this->makeRequest('GET', $uri, $query, $formParameters, $file);
        }
    }

    public function getVacancyImage($id)
    {
        $query = [];
        $formParameters = [];
        $file = null;
        $this->setHeaders(['Content-type' => 'application/json']);
        $uri = self::VACANCY_API .'/'. $id . '/get-image';
        $vacancyImage = $this->makeRequestForFile('GET', $uri, $query, $formParameters, $file);
        $ex = self::getFileExtension($vacancyImage['contentType']);
        if ($ex){
            \Storage::put(self::IMAGE_PATH.$id.$ex, $vacancyImage['file']);
        }

    }

    public function saveApplication($query = [], $formParameters = [], $file = [])
    {
        $uri = self::CANDIDATE_API;

        if (isset($query['cv']) && $query['cv']){
            $uploadFile = self::fileUpload($query['cv']);
            $query['cv'] =  fopen($uploadFile['path'], 'r');
        }

        if (isset($query['cover_letter']) && $query['cover_letter']){
            $uploadFile = self::fileUpload($query['cover_letter']);
            $query['cover_letter'] =  fopen($uploadFile['path'], 'r');
        }

        foreach ($query as $key=>$value){
            array_push($file, [
                'name'=>$key,
                'contents'=>$value
            ]);
        }
        $query = [];
        return $this->makeRequest('POST', $uri, $query, $formParameters, $file);
    }

    public function saveVacancy($query = [], $formParameters = [], $file = null)
    {

        $this->setHeaders(['Content-type' => 'application/json']);
        $uri = self::VACANCY_API;
        return $this->makeRequest('POST', $uri, $query, $formParameters, $file);
    }

    public function __destruct()
    {
        Promise\unwrap($this->promises);
    }

    private function fileUpload($file){
        if ($file) {
            $extension = strtolower($file->getClientOriginalExtension());
            $mime = $file->getmimeType();
            $fileName = uniqid(rand(), false);
            $file->move(storage_path(self::FILE_PATH), $fileName . '.' . $extension);
            $filePath = storage_path(self::FILE_PATH).$fileName . '.' . $extension;
            return [
                'path'=>$filePath,
                'fileName'=>$fileName,
                'extension'=>$extension,
                'mime'=>$mime,
            ];
        } else {
            return null;
        }
    }

    private function getFileExtension($contentType)
    {
        $pieces = explode('/', $contentType[0]);
        if ($pieces[0]=='image'){
            return '.' . array_pop($pieces);
        }else{
            return null;
        }

    }
}