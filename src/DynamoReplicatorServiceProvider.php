<?php
namespace LeandroPerini\DynamoReplicator;

use Illuminate\Support\ServiceProvider;

class DynamoReplicatorServiceProvider extends ServiceProvider {

   /**
    * Bootstrap the application services.
    *
    * @return void
    */
   public function boot()
   {
        if ($this->app->runningInConsole()) {
            $this->commands([
                DynamoReplicator::class,
            ]);
        }
   }

   /**
    * Register the application services.
    *
    * @return void
    */
   public function register()
   {
      //
  }

}