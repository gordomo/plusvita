<?php

namespace App\Twig;

use App\Repository\ConsumibleRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ConsumibleExtension extends AbstractExtension
{
    private $consumibleRepository;
    
    public function __construct(ConsumibleRepository $consumibleRepository)
    {
        $this->consumibleRepository = $consumibleRepository;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('get_consumible', [$this, 'getConsumible']),
        ];
    }

    public function getConsumible($id)
    {
        if (!$id) {
            return null;
        }
        
        return $this->consumibleRepository->find($id);
    }
}
