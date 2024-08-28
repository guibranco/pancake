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

    private function addComponent($component, &$badge)
    {
        if (isset($component) && (!empty($component) || $component === 0)) {
            $value = $this->encodeShieldsIoParameters($component);
            $badge[] = $value;
        }
    }

    private function addQueryString($component, $key, &$queryString)
    {
        if (isset($component) && !empty($component)) {
            $queryString[$key] = $component;
        }
    }

    public function generateBadgeUrl($label, $content, $color, $style, $labelColor, $logo)
    {
        $badge = array();
        $this->addComponent($label, $badge);
        $this->addComponent($content, $badge);
        $this->addComponent($color, $badge);

        $queryString = array();
        $this->addQueryString($style, "style", $queryString);
        $this->addQueryString($labelColor, "labelColor", $queryString);
        $this->addQueryString($logo, "logo", $queryString);

        return "https://img.shields.io/badge/" . implode("-", $badge) . "?" . http_build_query($queryString);
    }
}
