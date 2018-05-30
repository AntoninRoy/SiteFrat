<?php 

namespace App\Service;

class FacebookService
{
    // Ces variables réceptionnerons nos identifiants
	private $appId;
	private $appSecret;
	private $pageID;
	private $token;
    // Cette variable contiendra notre connexion avec l’API et sera utilisé pour interagir 
	private $connection;

	public function __construct($app_id,$app_secret,$page_id,$token)
	{
		$this->appId = $app_id;
		$this->appSecret = $app_secret;
		$this->pageID = $page_id;
		$this->token = $token;

        // Cette instruction nous permettra de nous connecter à l'API
		$this->connection = new \Facebook\Facebook([
		  'app_id' => $this->appId,
		  'app_secret' => $this->appSecret,
		  'default_graph_version' => 'v2.8',
		]);

	}

	// Cette fonction permettra de poster sur notre page Facebook
	public function poster($msg)
	{

		//cette array contendra les paramètres de notre requête, ici on se contente d'envoyer un texte, mais on pourrait envoyer également avec d'autre paramêtre un lien, une image, etc...
		$attachment = array(
			'access_token' => $this->token,
            'link' => 'http://antonin-roy.fr/app.php/',
			'message' => "Retrouvez un nouvel article sur le site internet de la Frat' pour connaître 
			tous les résultats du concours de twirling de ce week end ! </br> Bonne lecture !",
           		);

		// on poste sur notre page Facebook
		$this->connection->post('/'.$this->pageID.'/feed', $attachment, $this->token);
//https://developers.facebook.com/docs/sharing/opengraph/object-properties?hc_location=ufi#standard
	}
}