<?php
namespace Rotor;

class Regex
{
    const DIGITS = '\d+';
    const ALPHANUM = '[A-Za-z0-9]+';
    CONST SLUG = '[A-Za-z0-9][A-Za-z0-9-_]*';
    const ID = '[1-9]\d*';
    const IDS = '[1-9][\d+,]+\d|\d+';
    const STRID = '[0-9a-z]{20}';
    const ALPHA = '[A-Za-z]+';
}