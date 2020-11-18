<?php

namespace Drupal\ipc_weather\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class WeatherController.
 */
class WeatherController extends ControllerBase {

  /**
   * Drupal\Core\Routing\RequestContext definition.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $routerRequestContext;

  /**
   * Guzzle\Client instance.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * Array of weather data received.
   *
   * @var array
   */
  private $weatherData;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->httpClient = $container->get('http_client');
    return $instance;
  }

  /**
   * Showcase the weather.
   *
   * @param string
   *   The city for weather query.
   *
   * @return array
   *   Return array for theme values.
   */
  public function showcase($city) {

    return [
      '#theme' => 'ipc_weather',
      '#weather_data' => $this->getWeather($city),
      '#attached' => [
        'library' => [
          'ipc_weather/ipc_weather'
        ],
      ],
    ];
  }

  /**
   * Helper function to get weather info from rest API.
   *
   * @param string
   *   City needed for query.
   *
   * @return mixed
   *   Returns an array of weather data.
   */
  private function getWeather($city) {
    $url = 'http://api.openweathermap.org/data/2.5/weather?q=' . $city . '&APPID=91c2de4851f618f95235e7e23af1e0e9';
    $icon_prefix = 'http://openweathermap.org/img/wn/';

    try {
      $weather_data = $this->httpClient
        ->get($url)
        ->getBody()
        ->getContents();

      $weather_data = json_decode($weather_data, TRUE);

      $this->weatherData = [
        'location' => $weather_data['name'],
        'description' => $weather_data['weather'][0]['main'],
        'icon' => $icon_prefix . $weather_data['weather'][0]['icon'] . '@2x.png',
        'temp' => $this->kelvinToFahrenheit($weather_data['main']['temp']),
        'feels_like' => $this->kelvinToFahrenheit($weather_data['main']['feels_like']),
        'high' => $this->kelvinToFahrenheit($weather_data['main']['temp_max']),
        'low' => $this->kelvinToFahrenheit($weather_data['main']['temp_min']),
      ];

      return $this->weatherData;
    }
    catch (\Exception $exception) {
      \Drupal::messenger()->addError($exception->getMessage());
    }
  }

  /**
   * Helper function to convert kelvin to fahrenheit.
   *
   * @param float|int $given_value
   *   Kelvin value temperature.
   *
   * @return float|int
   *   Returns temp in fahrenheit.
   */
  private function kelvinToFahrenheit($given_value) {
    if ( !is_numeric($given_value) ) {
      return false;
    }
    return round((($given_value - 273.15) * 1.8) + 32);
  }

}
