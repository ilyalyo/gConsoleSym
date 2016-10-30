<?php
namespace AppBundle\Entity;

use FOS\UserBundle\Model\User as BaseUser;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\Role\Role;

/**
 * @ORM\Entity
 * @ORM\Table(name="fos_user")
 */
class User extends BaseUser
{
    const ROLE_APPROVED = 'ROLE_APPROVED';
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    public function toArray(){
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'hasRole' => $this->hasRole(self::ROLE_APPROVED) ? 'yes' : 'no',
        ];
    }
}
