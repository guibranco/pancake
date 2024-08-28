<?php

namespace GuiBranco\Pancake;

class ShieldsIo
{
    private function encodeShieldsIoParameters($input)
    {
        if (empty($input)) {
            return $input;
        }
        
        $input = str_replace("_", "__", $input);
        $input = str_replace(" ", "_", $input);
        $input = str_replace("-", "--", $input);
        $input = str_replace("/", "%2F", $input);
        $input = str_replace("#", "♯", $input);

        return $input;
    }

    public function generateBadgeUrl($label, $content, $color, $style, $labelColor, $logo)
    {
        $badge = array();

        if (isset($label) && $label !== null) {
            $label = $this->encodeShieldsIoParameters($label);
            $badge[] = $label;
        }

        if (isset($content) && $content !== null) {
            $content = $this->encodeShieldsIoParameters($content);
            $badge[] = $content;
        }

        if (isset($color) && $color !== null) {
            $badge[] = $color;
        }

        $queryString = array();
        if (isset($style) && $style !== null) {
            $queryString["style"] = $style;
        }

        if (isset($labelColor) && $labelColor !== null) {
            $queryString["labelColor"] = $labelColor;
        }

        if (isset($logo) && $logo !== null) {
            $queryString["logo"] = $logo;
        }

        return "https://img.shields.io/badge/" . implode("-", $badge) . "?" . http_build_query($queryString);
    }
}
