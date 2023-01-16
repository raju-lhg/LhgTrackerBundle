<?php

/*
 * This file is part of the DemoBundle for Kimai 2.
 * All rights reserved by Kevin Papst (www.kevinpapst.de).
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace KimaiPlugin\LhgTrackerBundle\Controller;

use App\Controller\AbstractController; 
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="/admin/lhg-tracker")
 * @Security("is_granted('demo')")
 */
// final class LhgTrackerController extends AbstractController
final class LhgTrackerController
{
    public function __construct()
    {
        # code...
    }
   
}
