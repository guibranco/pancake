<?php

namespace GuiBranco\Pancake;

class ShieldsIo
{
    private function encodeShieldsIoParameters($input)
    {
        $input = str_replace("_", "__", $input);
        $input = str_replace(" ", "_", $input);
        $input = str_replace("-", "--", $input);
        $input = str_replace("/", "%2F", $input);
        $input = str_replace("#", "♯", $input);

        return $input;
    }

    public function generateBadgeUrl($label, $content, $color, $style, $labelColor, $logo)
    {
        $badge = "";

        if (isset($label) && !empty($label)) {
            $label = $this->encodeShieldsIoParameters($label);
            $badge .= $label;

            if (isset($content) || isset($color)) {
                $badge .= "-";
            }
        }

        if (isset($content) && !empty($content)) {
            $content = $this->encodeShieldsIoParameters($content);
            $badge .= $content;
            
            if (isset($color)) {
                $badge .= "-";
            }
        }

        if (isset($color) && !empty($color)) {
            $badge .= $color;
        }

        $queryString = "";
        if (isset($style) && !empty($style)) {
            $queryString .= "style=" . $style . "&";
        }

        if (isset($labelColor) && !empty($labelColor)) {
            $queryString .= "labelColor=" . $labelColor . "&";
        }

        if (isset($logo) && !empty($logo)) {
            $queryString .= "logo=" . $logo;
        }

        return "https://img.shields.io/badge/" . $badge . "?" . $queryString;
    }
}
