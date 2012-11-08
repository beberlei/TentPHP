<?php
/**
 * TentPHP
 *
 * LICENSE
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.txt.
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to kontakt@beberlei.de so I can send you a copy immediately.
 */

namespace TentPHP\Util;

/**
 * Mentions Parser API
 *
 * Can parse a string for mentions based on the common "^name" syntax.
 */
class Mentions
{
    public function extractMentions($text, $contextEntity, $character = "^")
    {
        $mentions     = array();
        $contextParts = parse_url($contextEntity);
        $contextHost  = $this->getContextHost($contextParts);

        if (preg_match_all('(('.preg_quote($character). '([^\'\s]+)))', $text, $matches, PREG_OFFSET_CAPTURE)) {

            foreach ($matches[2] as $match) {
                list($entity, $pos) = $match;

                $entity     = $this->normalizeEntity($entity, $contextHost, $contextParts['scheme']);
                $mentions[] = array(
                    'entity' => $entity,
                    'pos'    => $pos - 1,
                    'length' => strlen(rtrim($match[0], ")'?.!,;:"))+1
                );
            }
        }

        return $mentions;
    }

    private function getContextHost($contextParts)
    {
        $hostParts    = explode(".", $contextParts['host']);
        array_shift($hostParts);
        return implode(".", $hostParts);
    }

    /**
     * From a short entityname this method normalizes the entity
     * to the full entity name given the current users entity name
     * as a reference.
     *
     * @param string $mention
     * @param string $contextEntity
     */
    public function normalize($mention, $contextEntity)
    {
        $contextParts = parse_url($contextEntity);
        $contextHost  = $this->getContextHost($contextParts);
        return $this->normalizeEntity($mention, $contextHost, $contextParts['scheme']);
    }

    private function normalizeEntity($entity, $contextHost, $scheme)
    {
        $entity = rtrim($entity, '.!?,');

        if (strpos($entity, "http") === false) {
            if(strpos($entity, ".") === false) {
                $entity = $scheme . "://" . $entity . "." . $contextHost;
            } elseif (strpos($entity, $contextHost) === false) {
                $entity = "http://" . $entity;
            } else {
                $entity = $scheme . "://" . $entity;
            }
        }

        return $entity;
    }
}

