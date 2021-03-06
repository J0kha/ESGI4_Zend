<?php
/**
 * Created by PhpStorm.
 * User: francois.mathieu
 * Date: 22/12/2017
 * Time: 12:17
 */

namespace Application\Controller;

use Application\Entity\User;
use Application\Manager\UserManager;
use Zend\Http\Request;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Zend\View\Model\ViewModel;

/**
 * Class UserController
 *
 * @author François MATHIEU <francois.mathieu@livexp.fr>
 * @method FlashMessenger flashMessenger()
 * @method Request getRequest()
 */
final class UserController extends AbstractActionController
{
    /**
     * @var UserManager $userManager
     */
    private $userManager;

    /**
     * UserController constructor.
     * @param UserManager $userManager
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * @return ViewModel
     */
    public function indexAction(): ViewModel
    {
        return parent::indexAction();
    }

    /**
     * @return Response|ViewModel
     */
    public function createAction()
    {
        $form = $this->userManager->getForm();
        $user = new User();
        $form->bind($user);

        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);

            if ($form->isValid()) {
                $this->userManager->persistAndFlush($user);

                $flashMessenger = $this->flashMessenger();
                $flashMessenger->addSuccessMessage(sprintf('User %s is successfully created', $user->getUsername()));

                return $this->redirect()->toRoute('user');
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    /**
     * @return ViewModel
     */
    public function listAction(): ViewModel
    {
        $users = $this->userManager->getRepository()->findAll();

        return new ViewModel([
            'users' => $users
        ]);
    }

    /**
     * @return ViewModel|Response
     */
    public function detailAction()
    {
        $id = $this->params()->fromRoute('id');
        if (null === $user = $this->userManager->getRepository()->findOneBy(['id' => $id])) {
            return $this->redirect()->toRoute('user');
        }

        return new ViewModel([
            'user' => $user,
        ]);
    }

    /**
     * @return Response|ViewModel
     */
    public function editAction()
    {
        $id = $this->params()->fromRoute('id');
        if (null === $user = $this->userManager->getRepository()->findOneBy(['id' => $id])) {
            return $this->redirect()->toRoute('user');
        }

        $form = $this->userManager->getForm();
        $form->bind($user);

        /** @var Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);

            if ($form->isValid()) {
                $this->userManager->persistAndFlush($user);

                $flashMessenger = $this->flashMessenger();
                $flashMessenger->addSuccessMessage(sprintf('User %s is successfully updated', $user->getUsername()));

                return $this->redirect()->toRoute('user');
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    /**
     * @return Response
     */
    public function deleteAction(): Response
    {
        $id = $this->params()->fromRoute('id');
        if (null === $user = $this->userManager->getRepository()->findOneBy(['id' => $id])) {
            return $this->redirect()->toRoute('user');
        }

        $flashMessenger = $this->flashMessenger();
        if ($user->hasOrganizedMeetup()) {
            $flashMessenger->addErrorMessage(sprintf('User %s is organizing Meetups, he cannot be removed', $user->getUsername()));

            return $this->redirect()->toRoute('user');
        }

        if ($user->hasOrganization()) {
            $flashMessenger->addErrorMessage(sprintf('User %s is leader of Organizations, he cannot be removed', $user->getUsername()));

            return $this->redirect()->toRoute('user');
        }

        $this->userManager->removeEntity($user);
        $flashMessenger->addSuccessMessage(sprintf('User %s successfully removed !', $user->getUsername()));

        return $this->redirect()->toRoute('user');
    }
}
