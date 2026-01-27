<?php

namespace BookneticApp\Backend\Appearance\Services;

use BookneticApp\Backend\Appearance\DTOs\Request\AppearanceRequest;
use BookneticApp\Backend\Appearance\DTOs\Response\AppearanceResponse;
use BookneticApp\Backend\Appearance\DTOs\Response\ListAppearanceResponse;
use BookneticApp\Backend\Appearance\Exceptions\DefaultThemeCantBeDeletedException;
use BookneticApp\Backend\Appearance\Exceptions\ThemeNotFoundException;
use BookneticApp\Backend\Appearance\Helpers\Theme;
use BookneticApp\Backend\Appearance\Mappers\AppearanceEditMapper;
use BookneticApp\Backend\Appearance\Repositories\AppearanceRepository;
use BookneticApp\Providers\Core\Capabilities;
use BookneticApp\Backend\Appearance\Mappers\AppearanceMapper;
use BookneticApp\Providers\Core\CapabilitiesException;

class AppearanceService
{
    private AppearanceRepository $repository;
    public function __construct()
    {
        $this->repository = new AppearanceRepository();
    }

    public function getAllAppearances(): ListAppearanceResponse
    {
        $appearances = $this->repository->getAllAppearances();

        $appearanceResponses = [];
        foreach ($appearances as $appearance) {
            $appearanceResponses[] = AppearanceMapper::toResponse($appearance);
        }

        return new ListAppearanceResponse($appearanceResponses);
    }

    /**
     * @throws CapabilitiesException
     */
    public function edit(int $id): AppearanceResponse
    {
        $defaultColors = Theme::$defaultColors;

        $appearance = $this->repository->getById($id);

        if ($appearance) {
            Capabilities::must('appearance_edit');

            $colors2 = json_decode($appearance['colors'], true);

            foreach ($defaultColors as $color_name => $color) {
                if (isset($colors2[ $color_name ]) && is_string($colors2[ $color_name ])) {
                    $defaultColors[ $color_name ] = htmlspecialchars($colors2[ $color_name ]);
                }
            }
        } else {
            Capabilities::must('appearance_add');

            $appearance = [
                'name'          =>  '',
                'fontfamily'    =>  'Poppins',
                'height'        =>  600,
                'hide_steps'    =>  '0',
                'use_local_font' => '0'
            ];
        }

        return AppearanceEditMapper::toResponse([
            'id'		=> $id,
            'info'		=> $appearance,
            'colors'	=> $defaultColors,
            'css_file'  => Theme::getThemeCss($id)
        ]);
    }

    /**
     * @throws ThemeNotFoundException
     * @throws DefaultThemeCantBeDeletedException
     */
    public function delete(int $id): void
    {
        if (!($id > 0)) {
            throw new ThemeNotFoundException();
        }

        $theme = $this->repository->getById($id);

        if (!$theme) {
            throw new ThemeNotFoundException();
        }

        if ($theme['is_default']) {
            throw new DefaultThemeCantBeDeletedException();
        }

        $this->repository->delete($id);
    }

    public function save(int $id, AppearanceRequest $request): void
    {
        $colors = json_decode($request->getColors(), true);

        $defaultColors = Theme::$defaultColors;

        foreach ($defaultColors as $color_name => $color) {
            if (isset($colors[ $color_name ]) && is_string($colors[ $color_name ]) && preg_match('/#[a-zA-Z0-9]{1,8}/', $colors[ $color_name ])) {
                $defaultColors[ $color_name ] = $colors[ $color_name ];
            }
        }

        $colors = json_encode($defaultColors);

        if ($id > 0) {
            $this->repository->update($id, [
                'name'	        =>	$request->getName(),
                'custom_css'	=>	$request->getCustomCss(),
                'colors'        =>	$colors,
                'height'        =>  $request->getHeight(),
                'fontfamily'    =>  $request->getFontFamily(),
                'hide_steps'    =>  $request->getHideSteps(),
                'use_local_font' => $request->getUseLocalFont(),
            ]);

            Theme::createThemeCssFile($id);
        } else {
            $this->repository->insert([
                'name'	        =>	$request->getName(),
                'custom_css'	=>	$request->getCustomCss(),
                'colors'	    =>	$colors,
                'height'        =>  $request->getHeight(),
                'fontfamily'    =>  $request->getFontFamily(),
                'hide_steps'    =>  $request->getHideSteps(),
                'use_local_font' => $request->getUseLocalFont(),
            ]);
        }
    }

    /**
     * @throws ThemeNotFoundException
     */
    public function selectDefaultAppearance(int $id): void
    {
        if (! ($id > 0)) {
            throw new ThemeNotFoundException();
        }

        $appearance = $this->repository->getById($id);

        if ($appearance === null) {
            throw new ThemeNotFoundException();
        }

        $this->repository->selectDefaultAppearance($id);
    }
}
