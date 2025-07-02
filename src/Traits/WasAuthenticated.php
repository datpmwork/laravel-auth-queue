<?php

namespace DatPM\LaravelAuthQueue\Traits;

use DatPM\LaravelAuthQueue\Middlewares\RestoreAuthenticatedContextMiddleware;

trait WasAuthenticated
{
    protected $authenticatedUser;

    /**
     * Override __sleep to work with SerializesModels
     */
    public function __sleep()
    {
        // Ensure auth context is captured before serialization
        $this->captureAuthContext();

        // If using SerializesModels, call its __sleep method
        if (in_array('Illuminate\Queue\SerializesModels', class_uses_recursive($this))) {
            // Let SerializesModels handle its serialization
            $properties = $this->__sleepSerializesModels();
        } else {
            // Standard serialization
            $properties = array_keys(get_object_vars($this));
        }

        return $properties;
    }

    /**
     * Handle SerializesModels __sleep if trait is used
     */
    protected function __sleepSerializesModels()
    {
        $properties = array_keys(get_object_vars($this));

        // Let SerializesModels do its magic
        foreach ($properties as $property) {
            $value = $this->{$property};
            if ($this->isSerializableModel($value)) {
                $this->{$property} = $this->getSerializedPropertyValue($value);
            }
        }

        return $properties;
    }

    /**
     * Check if value should be serialized by SerializesModels
     */
    protected function isSerializableModel($value)
    {
        return method_exists($this, 'getSerializedPropertyValue') &&
            (is_object($value) && method_exists($value, 'getQueueableId'));
    }

    /**
     * Capture and customize auth context
     */
    protected function captureAuthContext()
    {
        $user = auth()->user();
        if ($user) {
            // Customize serialization based on your needs
            $this->authenticatedUser = $user;
        }
    }

    /**
     * The job should go through AuthenticatedMiddleware
     */
    public function middleware()
    {
        return [new RestoreAuthenticatedContextMiddleware($this->authenticatedUser ?: auth()->user())];
    }
}
