<?php

namespace BookneticApp\Backend\Appearance\Controllers;

use BookneticApp\Backend\Appearance\DTOs\Request\AppearanceRequest;
use BookneticApp\Backend\Appearance\Exceptions\DefaultThemeCantBeDeletedException;
use BookneticApp\Backend\Appearance\Exceptions\ThemeNotFoundException;
use BookneticApp\Backend\Appearance\Services\FontService;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Providers\Core\CapabilitiesException;
use BookneticApp\Providers\Request\Post;
use BookneticApp\Providers\Core\Controller;
use BookneticApp\Backend\Appearance\Services\AppearanceService;
use BookneticApp\Backend\Appearance\Exceptions\InvalidFontNameException;
use BookneticApp\Backend\Appearance\Exceptions\InvalidHeightException;
use BookneticApp\Backend\Appearance\Exceptions\RequiredFieldMissingException;

class AppearanceAjaxController extends Controller
{
    private AppearanceService $service;
    private FontService $fontService;

    public function __construct()
    {
        $this->service = new AppearanceService();
        $this->fontService = new FontService();
    }

    /**
     * @throws InvalidHeightException
     * @throws CapabilitiesException
     * @throws InvalidFontNameException
     * @throws RequiredFieldMissingException
     */
    public function save()
    {
        $id = Post::int('id');

        if ($id > 0) {
            Capabilities::must('appearance_edit');
        } else {
            Capabilities::must('appearance_add');
        }

        $request = $this->prepareSaveRequestDTO();

        $this->service->save($id, $request);

        return $this->response(true);
    }

    /**
     * @throws ThemeNotFoundException
     * @throws CapabilitiesException
     * @throws DefaultThemeCantBeDeletedException
     */
    public function delete()
    {
        Capabilities::must('appearance_delete');

        $id = Post::int('id');

        $this->service->delete($id);

        return $this->response(true);
    }

    /**
     * @throws CapabilitiesException
     * @throws ThemeNotFoundException
     */
    public function selectDefaultAppearance()
    {
        Capabilities::must('appearance_select');

        $id = Post::int('id');

        $this->service->selectDefaultAppearance($id);

        return $this->response(true);
    }

    public function getFontFamilies()
    {
        $fontList = $this->fontService->getFontsList();

        return $this->response(true, [ 'results' => $fontList
     ]);
    }

    public function checkFontDownloaded()
    {
        $fontName = Post::string("font_name");
        if (empty($fontName)) {
            return $this->response(false);
        }

        $state = $this->fontService->checkFontStatus($fontName);

        return $this->response(true, [
            "state" => $state
        ]);
    }

    public function downloadFont()
    {
        $fontName = Post::string('font_name');

        if (empty($fontName)) {
            return $this->response(false);
        }

        $this->fontService->downloadFont($fontName);

        return $this->response(true);
    }

    /**
     * @return AppearanceRequest
     * @throws InvalidFontNameException
     * @throws InvalidHeightException
     * @throws RequiredFieldMissingException
     */
    public function prepareSaveRequestDTO(): AppearanceRequest
    {
        $request = new AppearanceRequest();

        $name = Post::string('name');
        $customCss = Post::string('custom_css');
        $height = Post::int('height');
        $fontFamily = Post::string('fontfamily');
        $colors = Post::string('colors');
        $hideSteps = Post::int('hide_steps', 0, [0, 1]);
        $useLocalFont = Post::int('use_local_font', 0, [0,1]);

        $request->setName($name)
                ->setCustomCss($customCss)
                ->setHeight($height)
                ->setFontFamily($fontFamily)
                ->setColors($colors)
                ->setHideSteps($hideSteps)
                ->setUseLocalFont($useLocalFont);

        return $request;
    }
}
