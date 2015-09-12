using UnityEngine;
using UnityEngine.UI;


// Change the material when certain poses are made with the Myo armband.
// Vibrate the Myo armband when a fist pose is made.
public class MyoGrabbableObject : MonoBehaviour
{
    [HideInInspector]
    public bool onHoldIn;
    [SerializeField]
    private Transform handTransform;
    public Vector3 offset;
   
    private GameObject handObject;
    private ColorBoxByPose hand;

    private Text myText;
    public float fadeTime;
    public bool displayInfo = false;

    //private int timer = 0;

    void Start()
    {
        onHoldIn = false;
        handObject = GameObject.FindGameObjectWithTag("hand");
        hand = handObject.GetComponent<ColorBoxByPose>();

        myText = GetComponentInChildren<Text>();
        myText.color = Color.clear;
    }

    void OnTriggerEnter(Collider other)
    {
        if (!onHoldIn)
        {
            Debug.Log("Estoy dentro shabo");
            hand.UpdateChemical(this);
            onHoldIn = true;
            displayInfo = true;
        }
          
    }

    void OnTriggerExit(Collider other)
    {
        onHoldIn = false;
        displayInfo = false;
    }


    void Update()
    {
        FadeText();

        if (hand.grab)
        {
            // Si estoy en el estado grab, el objeto sigue a la mano.
            Debug.Log(handTransform.transform.position);
            hand.chemical.transform.position = handTransform.transform.position + offset; 

            if (hand.release)
            {
                onHoldIn = false;
            }               
        }
    }
    void FadeText()
    {
        if (displayInfo)
        {
            myText.color = Color.Lerp(myText.color, Color.white, fadeTime * Time.deltaTime);
        }
        else
        {
            myText.color = Color.Lerp(myText.color, Color.clear, fadeTime * Time.deltaTime);
        }
    }

}
